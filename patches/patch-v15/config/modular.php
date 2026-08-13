<?php

return [
    'path' => '/var/www/www-root/data/www/osmo-avg.ru/app/Modules',
    'base_namespace' => 'App\Modules',
    'groupWithoutPrefix' => 'Pub',
    'groupMidleware' => [
        'Admin' => [
            'web' => [
                'auth',
            ],
            'api' => [
                'auth:api',
            ],
        ],
        'Pub' => [
            'web' => [
                'auth',
            ],
            'api' => [
                'auth:api',
            ],
        ],
        'Bitrix' => [
            'web' => [
                'auth',
            ],
            'api' => [
                'auth:api',
            ],
        ],
    ],
    'modules' => [
        'Admin' => [
        ],
        'Pub' => [
            'Analytics',
            'CrmMonitor',
            'ProposalTools',
            'DealCard',
            'PaymentCalendar',
            'ProposalVariantExtraPay',
            'LicenseKey',
            'ContractSpecificationScenario',
            'Report',
            'ContractSpecification',
            'Payment',
            'Contract',
            'ProposalPdfTemplate',
            'Hardware',
            'Software',
            'Work',
            'Log',
            'Proposal',
            'ProposalVariant',
            'Scenario',
            'ScenarioGroup',
            'Neuroservice',
            'NeuroserviceGroup',
            'Partner',
            'Company',
            'Files',
            'Constant',
            'UserNote',
            'Reminder',
            'Calendar',
            'Notify',
            'UserSettings',
            'Access',
            'AccessGroup',
            'Menu',
            'Dashboard',
            'UserDepartment',
            'UserGroup',
            'User',
            'Project',
            'ProjectConfiguration',
        ],
        'Bitrix' => [
            'Sync',
            'Dashboard',
            'CrmDeal',
        ],
    ],
];
