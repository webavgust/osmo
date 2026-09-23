<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Facades\Tools;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Неподписанные договоры (patch v30) — рамочные договоры без отметки о подписании
 * (`contracts.cb_signed = 0`), их спецификации и давность от даты договора.
 *
 * Отметка о подписании в портале одна — галочка «Подписан» в карточке договора;
 * скана договора в базе нет, поэтому «без скана» и «без подписи» здесь одно и то же.
 *
 * Архивные договоры (`old`) не прячутся — на карточке партнёра они тоже видны,
 * в списке они помечены. Сумма договора — суммы его спецификаций, кроме отменённых,
 * пересчитанные в валюту виджета по текущему курсу; договоры, для которых курса нет,
 * в сумму не попадают и считаются в skipped.
 */
class ContractsUnsignedWidget extends Widget
{
    /** Сколько строк держим в данных — вьюха режет их по высоте блока */
    public const MAX_ROWS = 30;

    public static function id(): string
    {
        return 'contracts_unsigned';
    }

    public static function name(): string
    {
        return 'Неподписанные договоры';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Рамочные договоры без отметки о подписании: партнёр, дата и давность';
    }

    public static function icon(): string
    {
        return 'fa-file-signature';
    }

    public static function sizes(): array
    {
        return ['8x4', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 170;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 900;
    }

    public static function available(User $user): bool
    {
        // договоры живут на карточке партнёра, а она открыта всем пользователям портала
        return true;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'type', 'type' => 'select', 'label' => 'Тип договора', 'default' => 'all',
                'options' => fn() => ['all' => 'Все типы'] + collect(ContractType::getActualDecorated())
                    ->map(fn($type) => (string) $type['label'])
                    ->all()],
            // по умолчанию — сколько влезет по высоте блока (не больше MAX_ROWS)
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько договоров в списке', 'default' => self::MAX_ROWS, 'min' => 1, 'max' => self::MAX_ROWS],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + SpecsReconcileWidget::partners()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $partner = (string) ($settings['partner'] ?? 'all');

        // договоры показывает карточка партнёра; партнёр не выбран — ведём в список
        return $partner !== 'all'
            ? route('partner.detail', (int) $partner)
            : route('partner.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец на MAX_ROWS строк — чтобы высокий блок было чем заполнить
        $partners = ['ГК Восток', 'МТ Интеграция', 'Shanghai Hengde Science & Tech Co', 'ООО «Телеком-Мастер»',
            'Риверсистемс', 'СТК', 'Альфа-Проект', 'ИнфоТех Сервис', 'Северный ветер', 'Digital Wave Ltd'];
        $types = ['services', 'license', 'platform'];

        $rows = [];
        $ages = ['fresh' => 0, 'mid' => 0, 'old' => 0, 'none' => 0];
        for ($i = 0; $i < static::MAX_ROWS; $i++) {
            // последний договор — без даты, как бывает в живых данных
            $days = $i === static::MAX_ROWS - 1 ? null : 418 - $i * 14;
            $specs = ($i * 7) % 5;
            $type = ContractType::from($types[$i % 3])->data();
            $ages[static::ageBucket($days)]++;

            $rows[] = [
                'partner' => $partners[$i % count($partners)], 'number' => sprintf('S-%s-%d/%d.26', ['AK', 'PG', 'OD'][$i % 3], 10 + $i, 1 + $i % 12),
                'type' => $type['label'], 'color' => $type['color'], 'icon' => $type['icon'],
                'date' => $days === null ? null : now()->subDays($days)->format('d.m.Y'), 'days' => $days,
                'specs' => $specs, 'amount' => $specs ? (($i * 37) % 50 + 1) * 85000.0 : 0.0,
                'old' => $i % 9 === 8, 'url' => null,
            ];
        }

        return [
            'rows' => $rows,
            'count' => count($rows), 'amount' => array_sum(array_column($rows, 'amount')),
            'specs' => array_sum(array_column($rows, 'specs')),
            'max_days' => 418, 'no_date' => 1, 'skipped' => 0, 'ages' => $ages, 'symbol' => '₽',
        ];
    }

    /**
     * Договоры без отметки о подписании и итоги по ним
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows', 'count', 'amount', 'specs', 'max_days', 'no_date', 'skipped', 'ages', 'symbol']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $partner = (string) ($settings['partner'] ?? 'all');
        $type = (string) ($settings['type'] ?? 'all');

        $builder = DB::table('contracts as c')
            ->leftJoin('partners as pa', 'pa.id', '=', 'c.partner_id')
            // отметки нет вовсе или она снята — и то и другое «не подписан»
            ->where(fn($builder) => $builder->where('c.cb_signed', 0)->orWhereNull('c.cb_signed'))
            ->select(['c.id', 'c.number', 'c.date', 'c.type', 'c.old', 'c.partner_id', 'pa.name as partner_name']);

        if ($partner !== 'all') {
            $builder->where('c.partner_id', (int) $partner);
        }

        if ($type !== 'all') {
            $builder->where('c.type', $type);
        }

        $contracts = collect($builder->get());
        $specs = static::specs($contracts->pluck('id')->all());

        $list = [];
        $total = ['count' => 0, 'amount' => 0.0, 'specs' => 0, 'max_days' => 0, 'no_date' => 0, 'skipped' => 0];
        // разбивка по давности — по всем договорам, а не только по попавшим в список
        $ages = ['fresh' => 0, 'mid' => 0, 'old' => 0, 'none' => 0];

        foreach ($contracts as $contract) {
            $date = $contract->date ? Carbon::parse($contract->date) : null;
            $days = $date ? (int) $date->startOfDay()->diffInDays(now()->startOfDay(), false) : null;
            $type_data = (ContractType::tryFrom((string) $contract->type) ?? ContractType::UNKNOWN)->data();

            // сумма договора — его спецификации, кроме отменённых, по текущему курсу
            $amount = 0.0;
            $count = 0;
            $skipped = false;

            foreach ($specs[$contract->id] ?? [] as $group) {
                $count += (int) $group->cnt;
                $converted = CurrencyService::convertAmount((float) $group->amount, $group->currency_slug, $currency, now());

                if ($converted === null) {
                    $skipped = true;
                    continue;
                }

                $amount += $converted;
            }

            $total['count']++;
            $total['specs'] += $count;
            $total['amount'] += $amount;
            if ($skipped) $total['skipped']++;
            if ($days === null) $total['no_date']++;
            if ($days !== null) $total['max_days'] = max($total['max_days'], $days);
            $ages[static::ageBucket($days)]++;

            $list[] = [
                'partner' => (string) ($contract->partner_name ?: '—'),
                'number' => (string) ($contract->number ?: 'б/н (' . $contract->id . ')'),
                'type' => (string) $type_data['label'],
                'color' => (string) $type_data['color'],
                'icon' => (string) $type_data['icon'],
                'date' => $date?->format('d.m.Y'),
                'days' => $days,
                'specs' => $count,
                'amount' => round($amount, 2),
                'old' => (bool) $contract->old,
                'url' => $contract->partner_id ? route('partner.detail', (int) $contract->partner_id) : null,
            ];
        }

        // сначала те, что висят дольше всех; договоры без даты — в конец
        usort($list, fn($a, $b) => [$b['days'] === null ? -1 : 1, $b['days'] ?? 0] <=> [$a['days'] === null ? -1 : 1, $a['days'] ?? 0]);

        return [
            'rows' => array_slice($list, 0, static::MAX_ROWS),
            'count' => $total['count'],
            'amount' => round($total['amount'], 2),
            'specs' => $total['specs'],
            'max_days' => $total['max_days'],
            'no_date' => $total['no_date'],
            'skipped' => $total['skipped'],
            'ages' => $ages,
            'symbol' => $ctx->symbol($currency),
        ];
    }

    /**
     * Спецификации договоров (кроме отменённых), сгруппированные по валюте
     *
     * @param array $ids id договоров
     * @return \Illuminate\Support\Collection ключ — id договора
     */
    protected static function specs(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();

        return collect(DB::table('contract_specifications')
            ->whereIn('contract_id', $ids)
            ->where('status', '!=', ContractSpecificationStatus::CANCELED->value)
            ->groupBy('contract_id', 'currency_slug')
            ->selectRaw('contract_id, currency_slug, COUNT(*) as cnt, SUM(amount) as amount')
            ->get())
            ->groupBy('contract_id');
    }

    /**
     * Корзина давности для разбивки: fresh (до 60 дней), mid (61–180), old (больше 180), none (без даты)
     *
     * @param int|null $days
     * @return string
     */
    public static function ageBucket(?int $days): string
    {
        if ($days === null) return 'none';

        return $days > 180 ? 'old' : ($days > 60 ? 'mid' : 'fresh');
    }

    /**
     * Давность по-человечески: 52 → «52 дня», 150 → «5 месяцев», 418 → «1 год»
     *
     * @param int|null $days
     * @return string
     */
    public static function age(?int $days): string
    {
        if ($days === null) return 'без даты';
        if ($days < 0) return 'впереди';
        if ($days < 60) return $days . ' ' . Tools::morph($days, 'день', 'дня', 'дней');

        $months = intdiv($days, 30);

        return $months < 12
            ? $months . ' ' . Tools::morph($months, 'месяц', 'месяца', 'месяцев')
            : intdiv($months, 12) . ' ' . Tools::morph(intdiv($months, 12), 'год', 'года', 'лет');
    }
}
