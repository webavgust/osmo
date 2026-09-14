<?php

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;

/**
 * Журнал изменений сущностей (patch v29).
 *
 * types — корни агрегатов: слаг ленты => класс модели. Модель считается корнем
 * ровно тогда, когда она перечислена здесь (HasLogger::isLogRoot()); остальные
 * подключённые модели — части, они поднимаются к корню через logParent().
 * Слаг должен совпадать с Model::logType().
 */
return [
    'types' => [
        'proposal' => Proposal::class,
        'partner' => Partner::class,
        'company' => Company::class,
        'deal_project' => DealProject::class,
    ],
];
