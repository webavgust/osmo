<?php

return [
    'path' => app_path('Modules'),
    'base_namespace' => 'App\Modules',
    'groupWithoutPrefix' => 'Pub',
    'groupMiddleware' => [
        'Admin' => [
            'web' => [
                'auth',
                'can:admin_panel',
            ],
            'api' => [
                'auth:api',
                'can:admin_panel',
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
            'Panel',
            'Users',
            'Consts',
        ],
        'Pub' => [
            'Desktop',
            'EntityLog',
            'DealProject',
            'ExternalProposal',
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
