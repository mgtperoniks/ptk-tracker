<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mapping role pembuat -> role approver
    |--------------------------------------------------------------------------
    | Contoh:
    | - Jika creator ber-role 'admin_qc' maka approver 'kabag_qc'
    | - Jika creator ber-role 'admin_hr' atau 'admin_k3' maka approver 'manager_hr'
    | - Default fallback: 'director'
    */
    'approval_map' => [
        'admin_qc' => 'kabag_qc',
        'admin_hr' => 'manager_hr',
        'admin_k3' => 'manager_hr',
        'admin_mtc' => 'kabag_mtc',

        // fallback jika role creator tidak ada di mapping
        '*' => 'director',
    ],
];
