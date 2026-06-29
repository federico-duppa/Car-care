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
