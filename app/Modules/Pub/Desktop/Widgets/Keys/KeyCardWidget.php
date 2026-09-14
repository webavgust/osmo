<?php

namespace App\Modules\Pub\Desktop\Widgets\Keys;

use App\Modules\Pub\Analytics\Services\LicenseRegistryService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;

/**
 * Карточка ключа (patch v30): один ключ на контроле — компания, спецификация,
 * дата окончания и сколько дней осталось.
 *
 * Ключ выбирается компанией (поле-ссылка, поиск по названию) и, если у компании
 * их несколько, номером ключа. Без номера берётся ближайший к окончанию из ещё
 * действующих, а если действующих нет — последний истёкший. Цвет состояния —
 * LicenseRegistryService::state(), как в реестре лицензий.
 *
 * Поиска по самим ключам в полях-ссылках нет (ApiDesktopSettingsController знает
 * КП, партнёра, компанию и сделку), поэтому номер ключа вводится строкой.
 */
class KeyCardWidget extends Widget
{
    public static function id(): string
    {
        return 'key_card';
    }

    public static function name(): string
    {
        return 'Карточка ключа';
    }

    public static function category(): string
    {
        return 'keys';
    }

    public static function description(): string
    {
        return 'Один ключ на контроле: компания, спецификация, окончание и остаток дней';
    }

    public static function icon(): string
    {
        return 'fa-key';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2', '4x4'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 140;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'entity', 'label' => 'Компания', 'entities' => ['company'], 'default' => null, 'hint' => 'Поиск по названию компании'],
            ['key' => 'code', 'type' => 'text', 'label' => 'Ключ', 'default' => '', 'max' => 128, 'hint' => 'Номер ключа; пусто — ближайший к окончанию ключ компании'],
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные ключи', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // страница ключей — реестр лицензий; поиск в нём идёт по номеру ключа и компании
        $code = trim((string) ($settings['code'] ?? ''));

        return route('analytics.licenses', array_filter(['q' => $code !== '' ? $code : null]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $state = LicenseRegistryService::state(LicenseRegistryService::bucket(21));

        return [
            'found' => true, 'code' => 'AV-2026-0417', 'company' => 'ООО «Альфа»', 'company_url' => null,
            'spec' => 'Платформа, 50 рабочих мест', 'contract' => '№ 12/2025', 'partner' => 'ГК Восток',
            'from' => now()->subDays(344)->format('d.m.Y'), 'to' => now()->addDays(21)->format('d.m.Y'),
            'days' => 21, 'active' => true, 'state_label' => $state['label'], 'state_color' => $state['color'],
        ];
    }

    /**
     * Выбранный ключ
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['found', 'code', 'company', 'company_url', 'spec', 'contract', 'partner',
     *     'from', 'to', 'days', 'active', 'state_label', 'state_color']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $empty = [
            'found' => false, 'code' => '', 'company' => '', 'company_url' => null, 'spec' => '',
            'contract' => '', 'partner' => '', 'from' => null, 'to' => null, 'days' => null,
            'active' => false, 'state_label' => '', 'state_color' => 'secondary',
        ];

        $company_id = (string) ($settings['target']['type'] ?? '') === 'company'
            ? (int) ($settings['target']['id'] ?? 0)
            : 0;
        $code = trim((string) $settings['code']);

        if ($company_id <= 0 && $code === '') {
            return $empty;
        }

        $keys = LicenseKey::query()
            ->with(['company', 'specification.contract.partner'])
            ->when(!empty($settings['only_active']), fn($query) => $query->where('active', 1))
            ->when($company_id > 0, fn($query) => $query->where('company_id', $company_id))
            ->when($code !== '', fn($query) => $query->where('key', $code))
            ->orderBy('active_to')
            ->get();

        // ближайший к окончанию из ещё действующих, иначе — последний истёкший
        $today = now()->startOfDay();
        $key = $keys->first(fn(LicenseKey $row) => $row->active_to === null || $row->active_to->gte($today)) ?? $keys->last();

        if ($key === null) {
            return $empty;
        }

        $days = $key->active_to ? (int) $today->diffInDays($key->active_to->copy()->startOfDay(), false) : null;
        $state = LicenseRegistryService::state(LicenseRegistryService::bucket($days));

        return [
            'found' => true,
            'code' => (string) $key->key,
            'company' => (string) ($key->company?->name ?? '—'),
            'company_url' => $key->company_id ? route('company.detail', $key->company_id) : null,
            'spec' => (string) ($key->specification?->name ?? ''),
            'contract' => (string) ($key->specification?->contract?->number ?? ''),
            'partner' => (string) ($key->specification?->contract?->partner?->name ?? ''),
            'from' => $key->active_from?->format('d.m.Y'),
            'to' => $key->active_to?->format('d.m.Y'),
            'days' => $days,
            'active' => (bool) $key->active,
            'state_label' => $state['label'],
            'state_color' => $state['color'],
        ];
    }
}
