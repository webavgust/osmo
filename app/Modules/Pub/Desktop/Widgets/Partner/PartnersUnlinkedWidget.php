<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * Партнёры без Битрикс24 (patch v30): те, кому не сопоставлена ни одна компания
 * Битрикса (`partner_crm_companies`, patch v23).
 *
 * Без сопоставления сделки и проекты Битрикса к партнёру не привязываются: их
 * не будет ни на карточке партнёра, ни в скоринге. Сопоставление правится в
 * форме партнёра, туда и ведёт ссылка со строки.
 *
 * Компании Битрикса живут в другой базе, но сюда они не нужны: достаточно
 * наличия строки сопоставления в нашей таблице — кросс-базовых JOIN'ов нет.
 */
class PartnersUnlinkedWidget extends Widget
{
    public static function id(): string { return 'partners_unlinked'; }

    public static function name(): string { return 'Партнёры без Битрикс24'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Партнёры без сопоставления с компаниями Битрикс24: их сделки не попадают в скоринг';
    }

    public static function icon(): string { return 'fa-link-slash'; }

    public static function sizes(): array { return ['8x4', '4x2']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 140; }

    public static function ttl(): int { return 600; }

    public static function fields(): array
    {
        return [
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные', 'default' => true,
                'hint' => 'Отключённых партнёров сопоставлять обычно не нужно'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('partner.index');
    }

    /**
     * Образцовые данные: 24 партнёра, чтобы было чем заполнить высокий блок
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $names = ['T1', 'i-fab', 'Группа Полипластик', 'Аксофт', 'Луч', 'Вектор', 'Прагма', 'Меридиан', 'Альтаир',
            'Спектр', 'Интегра', 'Форсайт', 'Орион', 'Квант', 'Сигма', 'Гранит', 'Ладога', 'Контур', 'Эталон',
            'Атлант', 'Байкал', 'Азимут', 'Полюс', 'Норд'];
        $regions = ['Москва', 'Санкт-Петербург', 'Казань', 'Новосибирск', '', 'Екатеринбург'];

        $rows = [];
        foreach ($names as $i => $name) {
            $rows[] = [
                'id' => $i + 1, 'name' => $name, 'region' => $regions[$i % count($regions)], 'active' => true,
                'companies' => ($i * 7) % 4, 'url' => null, 'edit_url' => null,
            ];
        }

        return ['count' => count($rows), 'total' => 63, 'linked' => 63 - count($rows), 'rows' => $rows];
    }

    /**
     * Партнёры без сопоставления с компаниями Битрикс24
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['count', 'total', 'linked', 'rows' => [['id', 'name', 'region', 'active',
     *     'companies', 'url', 'edit_url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $only_active = !empty($settings['only_active']);

        $partners = DB::table('partners as p')
            ->leftJoin('partner_crm_companies as l', 'l.partner_id', '=', 'p.id')
            ->whereNull('l.id')
            ->when($only_active, fn($query) => $query->where('p.active', 1))
            ->orderBy('p.name')
            ->get(['p.id', 'p.name', 'p.region', 'p.active']);

        $total = DB::table('partners')
            ->when($only_active, fn($query) => $query->where('active', 1))
            ->count();

        // сколько компаний портала висит на партнёре — чем их больше, тем заметнее пробел
        $companies = $partners->isEmpty() ? collect() : DB::table('companies')
            ->whereIn('partner_id', $partners->pluck('id'))
            ->groupBy('partner_id')
            ->selectRaw('partner_id, COUNT(*) as companies_count')
            ->pluck('companies_count', 'partner_id');

        $rows = $partners
            ->map(fn($partner) => [
                'id' => (int) $partner->id,
                'name' => (string) $partner->name,
                'region' => (string) ($partner->region ?? ''),
                'active' => (bool) $partner->active,
                'companies' => (int) ($companies[$partner->id] ?? 0),
                'url' => route('partner.detail', $partner->id),
                'edit_url' => route('partner.edit', $partner->id),
            ])
            ->values()
            ->all();

        return [
            'count' => count($rows),
            'total' => $total,
            'linked' => max(0, $total - count($rows)),
            'rows' => $rows,
        ];
    }
}
