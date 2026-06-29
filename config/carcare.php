<?php

return [
    /*
    | Display currency label for ARS amounts (e.g. ARS, USD, EUR). Cosmetic.
    */
    'currency' => env('APP_CURRENCY', 'ARS'),

    /*
    | USD anchor feature. When enabled, the app fetches the USD/ARS rate so
    | you can toggle every amount/calculation into USD. Each record stores the
    | rate of its own date (historical anchor), so old expenses keep their
    | real USD value regardless of later inflation.
    */
    'usd_enabled' => filter_var(env('CARCARE_USD_ENABLED', true), FILTER_VALIDATE_BOOL),

    /*
    | Which quote to use: blue | oficial | bolsa | contadoconliqui | mayorista.
    | "blue" is the usual purchasing-power anchor in Argentina.
    */
    'dolar_tipo' => env('CARCARE_DOLAR_TIPO', 'blue'),

    /*
    | Rate API endpoints (dolarapi.com for current, argentinadatos for history).
    | Both are free and need no key. Production must allow outbound HTTPS to them.
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
];
