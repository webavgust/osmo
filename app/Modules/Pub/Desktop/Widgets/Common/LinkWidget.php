<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use Illuminate\Support\Facades\Route;

/**
 * Быстрая ссылка (patch v30): карточка КП, партнёра, компании или сделки Битрикс24.
 */
class LinkWidget extends Widget
{
    public static function id(): string { return 'link'; }

    public static function name(): string { return 'Быстрая ссылка'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Ссылка на КП, партнёра, компанию или сделку: статус и сумма всегда перед глазами';
    }

    public static function icon(): string { return 'fa-link'; }

    public static function sizes(): array { return ['4x2', '2x2', '8x2']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 300; }

    public static function showTitle(): bool { return false; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'Объект', 'entities' => ['proposal', 'partner', 'company', 'deal'], 'required' => true, 'default' => null],
            ['key' => 'label_field', 'type' => 'select', 'label' => 'Название', 'default' => 'number_name', 'hint' => 'Для партнёра, компании и сделки — всегда название',
                'options' => ['number_name' => 'Номер и название', 'number' => 'Номер', 'name' => 'Название']],
            ['key' => 'second_line', 'type' => 'select', 'label' => 'Вторая строка', 'default' => 'auto',
                'options' => ['auto' => 'Статус и сумма / грейд / партнёр / стадия', 'none' => 'Нет']],
        ];
    }

    /**
     * Объект ссылки: ['found', 'missing', 'type', 'title', 'second', 'icon', 'url'];
     * missing — объект выбран, но не найден (удалён): пустое состояние говорит об этом, а не «выберите объект»
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $type = (string) ($settings['target']['type'] ?? '');
        $id = trim((string) ($settings['target']['id'] ?? ''));
        $out = ['found' => false, 'missing' => false, 'type' => $type, 'title' => '', 'second' => '', 'icon' => static::icon(), 'url' => null];

        if ($id === '') {
            return $out;
        }

        $found = match ($type) {
            'proposal' => $this->proposal($id, $settings['label_field']),
            'partner' => $this->partner($id),
            'company' => $this->company($id),
            'deal' => ctype_digit($id) ? $this->deal((int) $id, $ctx) : null,
            default => null,
        };

        if ($found === null) {
            return ['missing' => true] + $out;
        }

        if ($settings['second_line'] === 'none') {
            $found['second'] = '';
        }

        return ['found' => true, 'missing' => false, 'type' => $type] + $found;
    }

    /**
     * Образцовые данные для превью
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        return [
            'found' => true, 'missing' => false, 'type' => 'proposal', 'title' => 'КП № AA-794 · Платформа Восток',
            'second' => 'Выиграно · 3,1 млн ₽', 'icon' => 'fa-file-invoice', 'url' => null,
        ];
    }

    /** КП: последняя редакция группы */
    protected function proposal(string $group, string $label_field): ?array
    {
        $proposal = Proposal::with(['variants', 'currency'])->where('group', $group)->orderByDesc('iteration')->first();
        if (!$proposal) return null;

        $number = trim((string) $proposal->number) !== '' ? trim((string) $proposal->number) : 'б/н';
        $title = match ($label_field) {
            'number' => 'КП № ' . $number,
            'name' => (string) $proposal->name,
            default => '№ ' . $number . ' · ' . $proposal->name,
        };

        $cost = $proposal->variants->first()?->cost_total;
        $second = $proposal->status_decorate['label'] ?? '';
        if ($cost !== null) {
            $second .= ' · ' . static::money((float) $cost, (string) ($proposal->currency?->symbol ?? '₽'), short: true);
        }

        return ['title' => $title, 'second' => $second, 'icon' => 'fa-file-invoice', 'url' => route('proposal.detail', [$proposal, $proposal->iteration])];
    }

    /** Партнёр: название и грейд */
    protected function partner(string $id): ?array
    {
        $partner = ctype_digit($id) ? Partner::find((int) $id) : null;
        if (!$partner) return null;

        $grade = PartnerGrade::tryFrom((string) $partner->grade)?->data();

        return ['title' => (string) $partner->name, 'second' => (string) ($grade['label'] ?? ''), 'icon' => 'fa-handshake-simple', 'url' => route('partner.detail', $partner)];
    }

    /** Компания: название и партнёр */
    protected function company(string $id): ?array
    {
        $company = ctype_digit($id) ? Company::with('partner')->find((int) $id) : null;
        if (!$company) return null;

        return ['title' => (string) $company->name, 'second' => (string) ($company->partner?->name ?? ''), 'icon' => 'fa-building', 'url' => route('company.detail', $company)];
    }

    /**
     * Сделка Битрикс24: сводная карточка портала (она строится по КП, привязанному к сделке)
     * при праве deal_card_view, иначе — сделка в Битрикс24
     */
    protected function deal(int $id, DesktopContext $ctx): ?array
    {
        $deal = CrmDeal::find($id);
        if (!$deal) return null;

        $url = CrmDealRegistryService::url($id);
        if ($ctx->user && Route::has('deal_card.index') && $ctx->user->can_do('deal_card_view')) {
            $group = ProposalCrmDeal::where('crm_deal_id', $id)->orderByDesc('is_main')->value('proposal_group')
                ?? Proposal::where('crm_deal_id', $id)->value('group');
            if ($group) {
                $url = route('deal_card.index', $group);
            }
        }

        return ['title' => (string) $deal->title, 'second' => (string) $deal->stage_name, 'icon' => 'fa-handshake', 'url' => $url];
    }
}
