<?php

if (! function_exists('money')) {
    /**
     * Format an amount with the configured currency label,
     * e.g. money(12345.6) => "ARS 12.345,60".
     */
    function money($amount): string
    {
        return config('carcare.currency').' '.number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('current_currency')) {
    /** Active display currency for the request: 'ARS' or 'USD'. */
    function current_currency(): string
    {
        return session('moneda', 'ARS') === 'USD' ? 'USD' : 'ARS';
    }
}

if (! function_exists('current_usd_tipo')) {
    /** Active USD quote for the request: 'blue' or 'oficial'. */
    function current_usd_tipo(): string
    {
        $tipos = (array) config('carcare.usd_tipos', ['blue']);
        $tipo = session('usd_tipo', $tipos[0] ?? 'blue');

        return in_array($tipo, $tipos, true) ? $tipo : ($tipos[0] ?? 'blue');
    }
}

if (! function_exists('show_money')) {
    /**
     * Format an ARS amount in the active display currency. In USD mode it uses
     * the record's own historical rate when given, otherwise the current rate.
     * Falls back to ARS when no rate is available.
     */
    function show_money($ars, $rate = null): string
    {
        if (current_currency() === 'USD') {
            $r = ($rate && $rate > 0) ? (float) $rate : (float) config('carcare.usd_actual');
            if ($r > 0) {
                return 'USD '.number_format((float) $ars / $r, 2, ',', '.');
            }
        }

        return money($ars);
    }
}

if (! function_exists('money_active')) {
    /**
     * Format an amount that is ALREADY expressed in the active currency
     * (e.g. a total computed by VehiculoStats). Uses the active currency label.
     */
    function money_active($amount): string
    {
        $label = current_currency() === 'USD' ? 'USD' : config('carcare.currency');

        return $label.' '.number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('km')) {
    /** Format a kilometre figure, e.g. km(123456) => "123.456 km". */
    function km($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0, ',', '.').' km';
    }
}
