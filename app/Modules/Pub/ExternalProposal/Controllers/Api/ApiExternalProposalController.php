<?php

namespace App\Modules\Pub\ExternalProposal\Controllers\Api;

use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\ExternalProposal\Services\ExternalProposalService;
use Illuminate\Http\Request;

/**
 * AJAX КП OSMOVIEW CP (patch v21).
 *
 * Ошибки API и переноса отдаются как `{result: 'error', message}` со статусом
 * 200 — страница показывает toastr и живёт дальше, без 500.
 */
class ApiExternalProposalController
{
    private ExternalProposalService $service;

    public function __construct()
    {
        $this->service = new ExternalProposalService();
    }

    /**
     * Строки таблицы (bootstrap-table, пагинация на клиенте)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list_table(Request $request)
    {
        $rows = $this->service->rows($request->only(['q', 'transferred']));

        $data = $rows->map(function (ExternalProposal $row) {
            $proposal = $row->proposal;

            return [
                'id' => $row->id,
                'external_id' => $row->external_id,
                'number' => $row->external_number,
                'name' => $row->name,
                'customer' => $row->customer,
                'cameras' => $row->cameras,
                'date' => $row->created_at_remote?->format('Y-m-d'),
                'date_label' => $row->created_at_remote?->format('d.m.Y'),
                'updated' => $row->updated_at_remote?->format('d.m.Y H:i'),
                'has_payload' => $row->has_payload,
                'currency' => $row->has_payload ? $row->currency : null,
                'license' => $row->license_type,
                'vat' => $row->has_payload ? !empty($row->payload['isVatIncluded']) : null,
                'items' => $row->has_payload ? count($row->payload['items'] ?? []) : null,
                'works' => $row->has_payload ? count($row->payload['detailedWorks'] ?? []) : null,
                'transferred' => !empty($row->proposal_group),
                'transferred_at' => $row->transferred_at?->format('d.m.Y H:i'),
                'transferred_by' => $row->transferred_user?->full_name ?? $row->transferred_user?->name,
                'proposal' => $proposal ? [
                    'number' => $proposal->number,
                    'iteration' => $proposal->iteration,
                    'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
                ] : null,
                'link' => [
                    'detail' => route('external_proposal.box_detail', $row),
                    'transfer' => route('external_proposal.box_transfer', $row),
                    'fetch' => route('api.external_proposal.fetch', $row),
                ],
            ];
        })->values();

        return response()->json([
            'total' => $data->count(),
            'rows' => $data,
        ]);
    }

    /**
     * Обновить из API
     *
     * @return array
     */
    public function sync()
    {
        try {
            $result = $this->service->sync();
        } catch (\Throwable $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        $message = "Список: {$result['count']} записей (новых {$result['created']}, обновлено {$result['updated']}), detail загружено: {$result['details']}";
        if (!empty($result['errors'])) {
            $message .= '. Ошибки detail: ' . count($result['errors']);
        }

        return ['result' => 'success', 'message' => $message, 'data' => $result];
    }

    /**
     * Загрузить detail одной записи
     *
     * @param ExternalProposal $external
     * @return array
     */
    public function fetch(ExternalProposal $external)
    {
        try {
            $this->service->fetchDetail($external);
        } catch (\Throwable $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return ['result' => 'success', 'message' => 'Детальные данные загружены'];
    }

    /**
     * Ручной импорт JSON (textarea или файл)
     *
     * @param Request $request
     * @return array
     */
    public function import(Request $request)
    {
        $json = (string) $request->input('json', '');
        if ($request->hasFile('file')) {
            $json = (string) file_get_contents($request->file('file')->getRealPath());
        }

        if (trim($json) === '') {
            return ['result' => 'error', 'message' => 'Вставьте JSON или выберите файл'];
        }

        $external_id = trim((string) $request->input('external_id', '')) ?: null;

        try {
            $rows = $this->service->importJson($json, $external_id);
        } catch (\Throwable $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        $numbers = $rows->map(fn($row) => $row->external_number ?: $row->external_id)->implode(', ');

        return ['result' => 'success', 'message' => 'Импортировано записей: ' . $rows->count() . ' (' . $numbers . ')', 'count' => $rows->count()];
    }

    /**
     * Перенос в наше КП
     *
     * @param Request $request
     * @param ExternalProposal $external
     * @return array
     */
    public function transfer(Request $request, ExternalProposal $external)
    {
        $request->validate([
            'partner' => 'required|exists:partners,id',
            'company' => 'required|exists:companies,id',
            'manager' => 'required|exists:users,id',
            'number' => 'required|string|max:32',
            'scenario' => 'nullable|array',
            'scenario.*' => 'nullable|exists:scenarios,id',
            'force' => 'nullable|bool',
        ]);

        $choices = [
            'partner' => (int) $request->input('partner'),
            'company' => (int) $request->input('company'),
            'manager' => (int) $request->input('manager'),
            'number' => trim((string) $request->input('number')),
            'scenario' => collect((array) $request->input('scenario', []))
                ->filter(fn($id) => !empty($id))
                ->map(fn($id) => (int) $id)
                ->all(),
            'force' => $request->boolean('force'),
        ];

        try {
            $proposal = $this->service->transfer($external, $choices, $request->user());
        } catch (\Throwable $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return [
            'result' => 'success',
            'message' => 'Создано КП ' . $proposal->number,
            'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
            'proposal' => ['number' => $proposal->number, 'iteration' => $proposal->iteration],
        ];
    }

}
