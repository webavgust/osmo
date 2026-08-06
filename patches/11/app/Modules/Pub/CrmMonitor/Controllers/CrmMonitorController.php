<?php

namespace App\Modules\Pub\CrmMonitor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\CrmMonitor\Services\CrmMismatchService;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CrmMonitorController extends Controller
{
    /**
     * Монитор расхождений с Битрикс24
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $params = [
            'issue' => $request->input('issue'),
            'status' => $request->input('status'),
            'manager' => (int) $request->input('manager') ?: null,
            'q' => trim((string) $request->input('q')),
            'only_issues' => !$request->boolean('all'),
        ];

        // счётчики считаем по всем расхождениям, список — по фильтру
        $all = CrmMismatchService::rows(array_merge($params, ['issue' => null]));
        $rows = $params['issue']
            ? $all->filter(fn($row) => in_array($params['issue'], $row['issue_codes'], true))->values()
            : $all;

        return View::make('pub.crm_monitor.index', [
            'title' => 'Расхождения с Битрикс24',
            'params' => $params,
            'rows' => $rows,
            'issues' => CrmMismatchService::issues(),
            'counters' => CrmMismatchService::counters($all),
            'money' => CrmMismatchService::money($all),
            'statuses' => ProposalStatus::getDecorated(),
            'managers' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
