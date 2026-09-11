<?php

namespace App\Modules\Pub\ExternalProposal\Services;

use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\ExternalProposal\Mappers\OsmoviewCpMapper;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\ExternalProposal\Models\ExternalScenarioMap;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalVariantExtraPay\Services\ProposalVariantExtraPayService;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * КП OSMOVIEW CP: синхронизация, ручной импорт JSON и перенос в наше КП (patch v21).
 *
 * `sync()` — список из API → upsert по (source, external_id), затем detail
 * для строк без payload. `importJson()` — ручной ввод: принимает ответ
 * detail (`{"success":true,"data":{...}}`), голый `data` и ответ списка.
 * `transfer()` — маппер → `ProposalRepository::create()` → валюта, доп.
 * начисления, задачи, запоминание сопоставлений сценариев, отметка о переносе.
 * Переносятся все варианты КП сразу (см. `OsmoviewCpMapper::variants()`).
 */
class ExternalProposalService
{
    public const SOURCE = ExternalProposal::SOURCE_OSMOVIEW_CP;

    /** Сколько detail тянуть за один sync (чтобы не упереться в таймаут запроса) */
    public const DETAIL_BATCH = 30;

    private OsmoviewCpClient $client;

    public function __construct(?OsmoviewCpClient $client = null)
    {
        $this->client = $client ?? new OsmoviewCpClient();
    }

    /*** SYNC ***/

    /**
     * Обновить из API: список → upsert, затем detail для строк без payload
     *
     * @param bool $with_details тянуть ли detail
     * @return array ['count', 'created', 'updated', 'details', 'errors' => []]
     * @throws \RuntimeException если список недоступен
     */
    public function sync(bool $with_details = true): array
    {
        $rows = $this->client->list();

        $result = ['count' => count($rows), 'created' => 0, 'updated' => 0, 'details' => 0, 'errors' => []];

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['id'])) continue;

            $external = $this->upsertFromList($row);
            $external->wasRecentlyCreated ? $result['created']++ : $result['updated']++;
        }

        if ($with_details) {
            $pending = ExternalProposal::source(static::SOURCE)
                ->where(function ($builder) {
                    $builder->whereNull('payload')
                        ->orWhereColumn('fetched_at', '<', 'updated_at_remote');
                })
                ->orderBy('updated_at_remote', 'desc')
                ->limit(static::DETAIL_BATCH)
                ->get();

            foreach ($pending as $external) {
                try {
                    $this->fetchDetail($external);
                    $result['details']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = $external->external_id . ': ' . $e->getMessage();
                }
            }
        }

        return $result;
    }

    /**
     * Загрузить detail одной записи
     *
     * @param ExternalProposal $external
     * @return ExternalProposal
     * @throws \RuntimeException
     */
    public function fetchDetail(ExternalProposal $external): ExternalProposal
    {
        $data = $this->client->detail($external->external_id);

        return $this->upsertFromDetail($data, $external->external_id);
    }

    /**
     * Строка списка → запись (без payload)
     *
     * @param array $row
     * @return ExternalProposal
     */
    public function upsertFromList(array $row): ExternalProposal
    {
        $external = ExternalProposal::firstOrNew([
            'source' => static::SOURCE,
            'external_id' => (string) $row['id'],
        ]);

        $external->fill([
            'name' => static::limit($row['projectName'] ?? $external->name, 255),
            'customer' => static::limit($row['customerName'] ?? $external->customer, 255),
            'cameras' => isset($row['totalCameras']) ? (int) $row['totalCameras'] : $external->cameras,
            'created_at_remote' => static::dateRemote($row['createdAt'] ?? null) ?? $external->created_at_remote,
            'updated_at_remote' => static::dateTimeRemote($row['updatedAt'] ?? null) ?? $external->updated_at_remote,
            'list_payload' => $row,
        ]);
        $external->save();

        // detail, импортированный руками до синхронизации, лежит под синтетическим id — подбираем
        if (empty($external->payload)) {
            $this->adoptSynthetic($external);
        }

        return $external;
    }

    /**
     * Ответ detail → запись (с payload)
     *
     * @param array $data содержимое data
     * @param string|null $external_id doc-id, если известен
     * @return ExternalProposal
     */
    public function upsertFromDetail(array $data, ?string $external_id = null): ExternalProposal
    {
        $number = static::limit($data['id'] ?? null, 32);
        $updated = static::dateTimeRemote($data['updatedAt'] ?? null);
        $name = static::limit($data['projectName'] ?? null, 255);

        $external = null;
        if (!empty($external_id)) {
            $external = ExternalProposal::firstOrNew(['source' => static::SOURCE, 'external_id' => $external_id]);
        }

        // doc-id неизвестен (ручной импорт): ищем по номеру, затем по updatedAt + названию
        if (empty($external) && !empty($number)) {
            $external = ExternalProposal::source(static::SOURCE)->where('external_number', $number)->first();
        }
        if (empty($external) && !empty($updated) && !empty($name)) {
            $external = ExternalProposal::source(static::SOURCE)
                ->where('updated_at_remote', $updated)
                ->where('name', $name)
                ->first();
        }
        if (empty($external)) {
            $external = ExternalProposal::firstOrNew([
                'source' => static::SOURCE,
                'external_id' => $number ?: ('manual:' . md5(json_encode($data))),
            ]);
        }

        $external->fill([
            'external_number' => $number ?: $external->external_number,
            'name' => $name ?: $external->name,
            'customer' => static::limit($data['customerName'] ?? $external->customer, 255),
            'cameras' => isset($data['totalCameras']) ? (int) $data['totalCameras'] : $external->cameras,
            'created_at_remote' => static::dateRemote($data['createdAt'] ?? null) ?? $external->created_at_remote,
            'updated_at_remote' => $updated ?? $external->updated_at_remote,
            'payload' => $data,
            'fetched_at' => now(),
        ]);
        $external->save();

        return $external;
    }

    /**
     * Перенести payload с записи, созданной ручным импортом без doc-id
     * (external_id = номер), на настоящую запись из списка
     */
    private function adoptSynthetic(ExternalProposal $external): void
    {
        if (empty($external->updated_at_remote) || empty($external->name)) return;

        $synthetic = ExternalProposal::source(static::SOURCE)
            ->where('id', '!=', $external->id)
            ->whereNotNull('payload')
            ->whereColumn('external_id', 'external_number')
            ->where('updated_at_remote', $external->updated_at_remote)
            ->where('name', $external->name)
            ->first();

        if (empty($synthetic)) return;

        $external->fill([
            'external_number' => $synthetic->external_number,
            'payload' => $synthetic->payload,
            'fetched_at' => $synthetic->fetched_at,
            'proposal_group' => $external->proposal_group ?? $synthetic->proposal_group,
            'transferred_at' => $external->transferred_at ?? $synthetic->transferred_at,
            'transferred_by' => $external->transferred_by ?? $synthetic->transferred_by,
        ])->save();

        $synthetic->delete();
    }

    /*** IMPORT ***/

    /**
     * Ручной импорт JSON: detail (`{"success":true,"data":{...}}` или голый
     * `data`), либо ответ списка. Возвращает затронутые записи.
     *
     * @param string|array $json
     * @param string|null $external_id doc-id для detail, если известен
     * @return Collection<ExternalProposal>
     * @throws \RuntimeException
     */
    public function importJson(string|array $json, ?string $external_id = null): Collection
    {
        if (is_string($json)) {
            $decoded = json_decode(trim($json), true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('Не удалось разобрать JSON: ' . json_last_error_msg());
            }
            $json = $decoded;
        }

        if (array_key_exists('success', $json) && !$json['success']) {
            throw new \RuntimeException('В ответе success = false: ' . ($json['error'] ?? $json['message'] ?? ''));
        }

        // дамп из браузера: {"list": <ответ list>, "details": {doc-id: <ответ detail>}}
        // так данные забирают руками, пока API закрыт авторизацией AI Studio
        if (isset($json['details']) && is_array($json['details'])) {
            $rows = collect();
            if (!empty($json['list'])) {
                $rows = $rows->merge($this->importJson($json['list']));
            }
            foreach ($json['details'] as $doc_id => $detail) {
                if (!is_array($detail)) continue;
                $rows->push($this->importJson($detail, is_string($doc_id) ? $doc_id : null)->first());
            }
            if ($rows->isEmpty()) throw new \RuntimeException('В дампе нет ни списка, ни деталей');

            return $rows->filter()->values();
        }

        $data = array_key_exists('data', $json) && (is_array($json['data'])) ? $json['data'] : $json;

        // список: массив строк с projectName
        if (array_is_list($data)) {
            $rows = collect();
            foreach ($data as $row) {
                if (!is_array($row) || empty($row['id'])) continue;
                $rows->push($this->upsertFromList($row));
            }
            if ($rows->isEmpty()) throw new \RuntimeException('В списке нет строк с полем id');

            return $rows;
        }

        // detail
        if (!isset($data['projectName']) && !isset($data['items']) && !isset($data['detailedWorks'])) {
            throw new \RuntimeException('JSON не похож ни на ответ detail (projectName, items, detailedWorks), ни на список');
        }

        return collect([$this->upsertFromDetail($data, $external_id)]);
    }

    /*** TRANSFER ***/

    /**
     * Перенос в наше КП
     *
     * @param ExternalProposal $external
     * @param array $choices partner, company, manager, number, scenario[i], force
     * @param User|null $user кто переносит
     * @return Proposal
     * @throws \RuntimeException
     */
    public function transfer(ExternalProposal $external, array $choices = [], ?User $user = null): Proposal
    {
        if (!$external->has_payload) {
            throw new \RuntimeException('У записи нет детальных данных — загрузите detail из API или импортируйте JSON');
        }

        if (!empty($external->proposal_group) && empty($choices['force'])) {
            throw new \RuntimeException('Запись уже перенесена в КП ' . ($external->proposal?->number ?? '') . ' — используйте «перенести ещё раз как новое КП»');
        }

        $user = $user ?? auth()->user();
        if (empty($choices['manager']) && $user) $choices['manager'] = $user->id;

        $preview = OsmoviewCpMapper::preview($external, $choices);
        $request = OsmoviewCpMapper::request($external, $choices, $preview);

        $proposal = ProposalRepository::create($request);

        // валюта: create() всегда ставит RUB — переводим в валюту КП с его курсом
        if ($preview['currency'] !== Currency::CURRENCY_DEFAULT) {
            $currency = CurrencyRepository::get($preview['currency']);
            if (!empty($currency)) {
                $proposal->update([
                    'currency_rate' => $preview['rate'],
                    'currency_rate_cumulative' => $preview['rate'],
                ]);
                $proposal->currency()->associate($currency)->save();
            }
        }

        // дополнительные начисления: доп. сборы источника (taxMode = custom_charges).
        // Считаются от сумм варианта, поэтому ставим их после create() — суммы
        // вариантов там уже посчитаны, и после смены валюты КП
        $charges = $preview['charges']['transfer'] ?? [];
        if (!empty($charges)) {
            $extra = ['percent' => array_map(
                // сбор из OSMOVIEW CP — налог на весь проект, значит блок «на всё»
                fn($charge) => ['name' => $charge['name'], 'type' => 'all', 'amount' => $charge['percent']],
                $charges
            )];

            foreach ($proposal->variants as $variant) {
                ProposalVariantExtraPayService::create($variant, $extra);
            }
        }

        // задачи: исходный запрос и описания — в блок «Задачи» варианта и КП
        $task = OsmoviewCpMapper::task($external, $preview);
        $proposal->forceFill(['task' => $task])->save();
        foreach ($proposal->variants as $variant) {
            $variant->update(['task' => $task]);
        }

        // оборудование: сервер и камеры из требований генератора
        $hardware = OsmoviewCpMapper::hardware($external, $preview);
        foreach ($proposal->variants as $variant) {
            foreach ($hardware as $row) {
                $variant->hardware()->create($row);
            }
        }

        // запоминаем сопоставления сценариев (кастомные "0" не запоминаем)
        foreach ($preview['scenarios'] as $row) {
            if (empty($row['scenario_id']) || $row['external_id'] === '' || $row['external_id'] === '0') continue;
            if (in_array($row['match'], ['choice', 'exact', 'similar'], true)) {
                ExternalScenarioMap::remember(static::SOURCE, $row['external_id'], (int) $row['scenario_id'], $row['name'], $user?->id);
            }
        }

        $external->update([
            'proposal_group' => $proposal->group,
            'transferred_at' => now(),
            'transferred_by' => $user?->id,
        ]);

        return $proposal->refresh();
    }

    /*** LIST ***/

    /**
     * Строки для таблицы
     *
     * @param array $params q, transferred (yes|no|all)
     * @return Collection<ExternalProposal>
     */
    public function rows(array $params = []): Collection
    {
        $builder = ExternalProposal::source(static::SOURCE)->with(['proposal', 'transferred_user']);

        $transferred = $params['transferred'] ?? 'all';
        if ($transferred === 'yes') $builder->transferred(true);
        if ($transferred === 'no') $builder->transferred(false);

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('customer', 'like', "%{$q}%")
                    ->orWhere('external_number', 'like', "%{$q}%")
                    ->orWhere('external_id', 'like', "%{$q}%");
            });
        }

        return $builder->orderBy('updated_at_remote', 'desc')->orderBy('id', 'desc')->get();
    }

    /*** HELPERS ***/

    private static function limit($value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    /**
     * "dd.mm.yyyy" → Carbon
     */
    private static function dateRemote($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        foreach (['d.m.Y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * ISO → Carbon
     */
    private static function dateTimeRemote($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
