<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCurrencyRates extends Command
{
    protected $signature = 'currency:fetch-rates
                            {--from=2025-01-01 : Дата начала (Y-m-d)}
                            {--to= : Дата окончания (Y-m-d, по умолчанию вчера)}';

    protected $description = 'Загружает курсы валют с сайта ЦБ РФ и вставляет напрямую в БД (таблица currency_rates)';

    protected array $currencies = ['INR', 'SGD', 'SRD', 'UZS', 'CNY', 'EUR', 'USD'];

    public function handle()
    {
        $from = Carbon::parse($this->option('from'));
        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::yesterday();

        if ($from->gt($to)) {
            $this->error('Дата начала не может быть позже даты окончания.');
            return 1;
        }

        $this->info("Начинаем загрузку курсов с {$from->toDateString()} по {$to->toDateString()}");

        $current = $from->copy();
        $totalDays = $from->diffInDays($to) + 1;
        $processed = 0;
        $success = 0;
        $errors = 0;

        while ($current->lte($to)) {
            sleep(1);
            $dateStr = $current->toDateString();
            $this->info("[{$processed}/{$totalDays}] Обработка даты: {$dateStr}");

            try {
                $rates = $this->fetchRatesForDate($current);
                if (empty($rates)) {
                    $this->warn("  → Нет данных за {$dateStr} (нерабочий день или ошибка)");
                    $errors++;
                } else {
                    $inserted = 0;
                    foreach ($rates as $slug => $amount) {
                        // Прямая вставка с проверкой на дубликат (UNIQUE ключ по date+slug)
                        DB::table('currency_rates')->updateOrInsert(
                            ['date' => $dateStr, 'slug' => $slug],
                            ['amount' => $amount]
                        );
                        $inserted++;
                    }
                    $this->info("  → Сохранено {$inserted} записей для {$dateStr}");
                    $success++;
                }
            } catch (\Exception $e) {
                $this->error("  → Ошибка: " . $e->getMessage());
                Log::error("Currency fetch failed for {$dateStr}: " . $e->getMessage());
                $errors++;
            }

            $processed++;
            $current->addDay();
            usleep(200000); // 0.2 сек
        }

        $this->newLine();
        $this->info("✅ Готово. Успешно обработано дней: {$success}, с ошибками: {$errors}");
        return 0;
    }

    private function fetchRatesForDate(Carbon $date): array
    {
        $dateFormatted = $date->format('d/m/Y');
        $url = "https://www.cbr.ru/scripts/XML_daily.asp?date_req={$dateFormatted}";

        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        $xml = simplexml_load_string($response->body());
        if ($xml === false) {
            throw new \Exception("Не удалось разобрать XML");
        }

        $result = [];

        foreach ($xml->Valute as $valute) {
            $charCode = (string) $valute->CharCode;
            if (!in_array($charCode, $this->currencies)) {
                continue;
            }

            $value = (float) str_replace(',', '.', $valute->Value);
            $nominal = (int) $valute->Nominal;
            $amount = $value / $nominal;

            $result[$charCode] = round($amount, 6);
        }

        return $result;
    }
}
