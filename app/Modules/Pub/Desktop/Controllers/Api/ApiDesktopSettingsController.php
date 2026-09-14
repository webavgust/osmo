<?php

namespace App\Modules\Pub\Desktop\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Modules\Pub\Desktop\Services\WidgetRegistry;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Support\UiTheme;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Попап настроек виджета и поиск объектов для полей-ссылок (patch v30, этап A4b).
 *
 * form — HTML попапа (шаблон components.box.box-static-large), ошибка доступа —
 * тот же попап с плашкой. search — JSON для select2: {results: [{id, text}]}.
 */
class ApiDesktopSettingsController extends Controller
{
    /** Подписи типов объектов для полей entity */
    public const ENTITIES = [
        'proposal' => 'КП',
        'partner' => 'Партнёр',
        'company' => 'Компания',
        'deal' => 'Сделка Битрикс24',
    ];

    /** Сколько вариантов отдаёт поиск */
    protected const LIMIT = 20;

    /**
     * Попап настроек: вход uid, widget, w, h, settings (JSON-строка)
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Contracts\View\View
     */
    public function form(Request $request, Desktop $desktop)
    {
        // группа api не проходит ResolveUiTheme, а стол свёрстан только в Metronic
        $this->useMetronic();

        try {
            DesktopService::assertEdit($desktop, auth()->user());
        } catch (AuthorizationException $e) {
            return $this->denied($e->getMessage());
        }

        $class = WidgetRegistry::find((string) $request->input('widget'));
        if ($class === null || !$class::available(auth()->user())) {
            return $this->denied('Виджет недоступен');
        }

        $raw = json_decode((string) $request->input('settings'), true);
        $values = $class::normalize(is_array($raw) ? $raw : []);

        $groups = ['data' => [], 'view' => []];
        $options = [];
        $captions = [];

        foreach ($class::schema() as $field) {
            $groups[$field['group'] ?? 'data'][] = $field;

            if (in_array($field['type'], ['select', 'currency', 'period', 'list'], true)) {
                $options[$field['key']] = $class::options($field);
            }

            if ($field['type'] === 'entity') {
                $value = $values[$field['key']] ?? null;
                $captions[$field['key']] = is_array($value) ? $this->caption((string) $value['type'], (string) $value['id']) : null;
            }
        }

        return View::make('pub.desktop.boxes.settings', [
            'title' => 'Настройки: ' . e($class::name()),
            'widget' => $class,
            'meta' => $class::meta(),
            'groups' => array_filter($groups),
            'values' => $values,
            'options' => $options,
            'captions' => $captions,
            'entities' => static::ENTITIES,
            'uid' => (string) $request->input('uid'),
            'w' => (int) $request->input('w'),
            'h' => (int) $request->input('h'),
        ]);
    }

    /**
     * Поиск объектов для select2: type (proposal|partner|company|deal), q (от 2 символов;
     * короче — самые свежие)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $type = (string) $request->input('type');
        $q = trim((string) $request->input('q'));
        $q = mb_strlen($q) >= 2 ? $q : '';

        try {
            $results = match ($type) {
                'proposal' => $this->proposals($q),
                'partner' => $this->partners($q),
                'company' => $this->companies($q),
                'deal' => $this->deals($q),
                default => [],
            };
        } catch (\Throwable $e) {
            report($e);
            $results = [];
        }

        return response()->json(['results' => array_values($results)]);
    }

    /**
     * Подпись выбранного объекта для <option selected> (как его находит LinkWidget)
     *
     * @param string $type
     * @param string $id
     * @return string|null null — объект не найден
     */
    public function caption(string $type, string $id): ?string
    {
        $id = trim($id);
        if ($id === '') return null;

        try {
            return match ($type) {
                'proposal' => ($p = Proposal::where('group', $id)->orderByDesc('iteration')->first()) ? $this->proposalText($p) : null,
                'partner' => ctype_digit($id) ? Partner::find((int) $id)?->name : null,
                'company' => ctype_digit($id) && ($c = Company::with('partner')->find((int) $id)) ? $this->companyText($c) : null,
                'deal' => ctype_digit($id) && ($d = CrmDeal::find((int) $id)) ? '#' . $d->id . ' · ' . $d->title : null,
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** КП: последние редакции, id = group */
    protected function proposals(string $q): array
    {
        return Proposal::latestIteration()
            ->when($q !== '', fn($query) => $query->where(fn($w) => $w->where('number', 'like', '%' . $q . '%')->orWhere('name', 'like', '%' . $q . '%')))
            ->orderByDesc('id')->limit(static::LIMIT)->get(['id', 'group', 'number', 'name'])
            ->map(fn($p) => ['id' => (string) $p->group, 'text' => $this->proposalText($p)])->all();
    }

    /** Партнёры по названию */
    protected function partners(string $q): array
    {
        return Partner::query()
            ->when($q !== '', fn($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->orderByDesc('id')->limit(static::LIMIT)->get(['id', 'name'])
            ->map(fn($p) => ['id' => (string) $p->id, 'text' => (string) $p->name])->all();
    }

    /** Компании по названию, с партнёром в подписи */
    protected function companies(string $q): array
    {
        return Company::with('partner:id,name')
            ->when($q !== '', fn($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->orderByDesc('id')->limit(static::LIMIT)->get(['id', 'name', 'partner_id'])
            ->map(fn($c) => ['id' => (string) $c->id, 'text' => $this->companyText($c)])->all();
    }

    /** Сделки Битрикс24 по названию и номеру */
    protected function deals(string $q): array
    {
        return CrmDeal::query()
            ->when($q !== '', fn($query) => $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%' . $q . '%');
                if (ctype_digit(ltrim($q, '#'))) $w->orWhere('id', (int) ltrim($q, '#'));
            }))
            ->orderByDesc('id')->limit(static::LIMIT)->get(['id', 'title'])
            ->map(fn($d) => ['id' => (string) $d->id, 'text' => '#' . $d->id . ' · ' . $d->title])->all();
    }

    /** «№ AA-794 · Название», пустой номер — «б/н» */
    protected function proposalText(Proposal $proposal): string
    {
        $number = trim((string) $proposal->number);

        return '№ ' . ($number !== '' ? $number : 'б/н') . ' · ' . $proposal->name;
    }

    /** «Компания · Партнёр» */
    protected function companyText(Company $company): string
    {
        return (string) $company->name . ($company->partner?->name ? ' · ' . $company->partner->name : '');
    }

    /**
     * Попап с понятным отказом вместо формы
     *
     * @param string $message
     * @return \Illuminate\Contracts\View\View
     */
    protected function denied(string $message)
    {
        return View::make('pub.desktop.boxes.settings', ['title' => 'Настройки виджета', 'error' => $message]);
    }

    /** Включить тему Metronic для вьюх попапа (как ApiDesktopController::useMetronic) */
    protected function useMetronic(): void
    {
        UiTheme::use('metronic');

        $path = UiTheme::viewsPath();
        if (!$path || !is_dir($path)) {
            return;
        }

        $finder = View::getFinder();
        if (!in_array(realpath($path), array_map('realpath', $finder->getPaths()), true)) {
            $finder->prependLocation($path);
        }
    }
}
