<?php

return [
    'admin' => [
        'require_two_factor' => (bool) env('ADMIN_REQUIRE_2FA', false),
        'roles' => [
            'super_admin' => 'Super administrator',
            'admin' => 'Administrator',
            'staff' => 'Staff',
        ],
    ],

    'account' => [
        'export_rate_limit' => 3,
    ],
];
