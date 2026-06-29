<?php

return [
    /*
    | Display currency label for ARS amounts (e.g. ARS, USD, EUR). Cosmetic.
    */
    'currency' => env('APP_CURRENCY', 'ARS'),

    /*
    | USD anchor. Always on, no configuration required. Each record snapshots
    | both the blue and official USD/ARS rate of its own date, so old expenses
    | keep their real USD value regardless of later inflation. A UI toggle picks
    | ARS vs USD and which quote (blue/oficial) to convert with.
    */
    'usd_tipos' => ['blue', 'oficial'],

    /*
    | Rate API endpoints (dolarapi.com for current, argentinadatos for history).
    | Both are free and need no key. Defaults work out of the box.
    */
    'rate_api' => [
        'current' => env('CARCARE_RATE_API_CURRENT', 'https://dolarapi.com/v1/dolares'),
        'history' => env('CARCARE_RATE_API_HISTORY', 'https://api.argentinadatos.com/v1/cotizaciones/dolares'),
        'timeout' => (int) env('CARCARE_RATE_API_TIMEOUT', 4),
    ],

    /*
    | Resolved at runtime by the ShareVehiculos middleware so the show_money()
    | helper can convert amounts that have no stored rate. Do not set by hand.
    */
    'usd_actual' => null,

    /*
    | Reminders engine. A reminder is "próximo" (soon) once it is within this
    | many km or days of its due point, and "vencido" (overdue) once past it.
    */
    'recordatorios' => [
        'aviso_km' => (int) env('CARCARE_AVISO_KM', 500),
        'aviso_dias' => (int) env('CARCARE_AVISO_DIAS', 14),
    ],
];
