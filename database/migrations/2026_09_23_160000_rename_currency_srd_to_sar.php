<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Саудовский риял: код SRD → SAR (решение владельца 23.09.2026).
 *
 * Валюту завели под кодом SRD (это суринамский доллар) и подписали «Саудовский реал»;
 * курс при этом всегда был курсом рияла — команда currency:fetch-rates переводила SAR
 * ЦБ в SRD. Код меняется везде, где валюта хранится строкой: справочник, курсы, КП,
 * договоры, спецификации, доплаты вариантов. Контекст столов (desktops.context) хранит
 * код валюты стола — тоже.
 */
return new class extends Migration
{
    /** Таблица => колонка с кодом валюты */
    private array $columns = [
        'currency_rates' => 'slug',
        'proposals' => 'currency_slug',
        'contracts' => 'currency_slug',
        'contract_specifications' => 'currency_slug',
        'proposal_variant_extra_pays' => 'currency',
    ];

    public function up()
    {
        $this->rename('SRD', 'SAR', 'Саудовский риял');
    }

    public function down()
    {
        $this->rename('SAR', 'SRD', 'Саудовский реал');
    }

    /**
     * Сменить код валюты во всех таблицах; повторный запуск ничего не меняет
     *
     * @param string $from
     * @param string $to
     * @param string $name
     * @return void
     */
    private function rename(string $from, string $to, string $name): void
    {
        if (!DB::table('currencies')->where('slug', $from)->exists()) return;

        DB::transaction(function () use ($from, $to, $name) {
            DB::table('currencies')->where('slug', $from)->update(['slug' => $to, 'name' => $name]);

            foreach ($this->columns as $table => $column) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }

            // валюта стола в контексте (JSON {"currency":"SRD",…})
            DB::table('desktops')->where('context', 'like', '%"' . $from . '"%')->get(['id', 'context'])
                ->each(function ($desktop) use ($from, $to) {
                    $context = json_decode((string) $desktop->context, true);
                    if (!is_array($context) || ($context['currency'] ?? null) !== $from) return;

                    $context['currency'] = $to;
                    DB::table('desktops')->where('id', $desktop->id)->update(['context' => json_encode($context)]);
                });
        });
    }
};
