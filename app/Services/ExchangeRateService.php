<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the USD/ARS rate (default: dólar blue) used as an inflation anchor.
 *
 * - current(): the latest rate, cached for a few hours.
 * - forDate(): the rate on a given date (historical). Past dates are
 *   immutable so they're cached basically forever; today/future fall back to
 *   current().
 *
 * Every network failure degrades gracefully to null — saving an expense or
 * rendering a page must never break because the rate API is down.
 */
class ExchangeRateService
{
    private string $tipo;

    public function __construct(?string $tipo = null)
    {
        $this->tipo = $tipo ?: (string) config('carcare.dolar_tipo', 'blue');
    }

    /** Latest rate (mid of buy/sell), or null. */
    public function current(): ?float
    {
        if (! config('carcare.usd_enabled')) {
            return null;
        }

        return Cache::remember("usd_rate:current:{$this->tipo}", now()->addHours(6), function () {
            $base = rtrim((string) config('carcare.rate_api.current'), '/');

            return $this->extract($this->get("{$base}/{$this->tipo}"));
        });
    }

    /** Rate on a specific date (historical anchor), or null. */
    public function forDate(Carbon|string $date): ?float
    {
        if (! config('carcare.usd_enabled')) {
            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Today or the future: use the live rate.
        if ($date->isToday() || $date->isFuture()) {
            return $this->current();
        }

        $key = "usd_rate:{$this->tipo}:{$date->toDateString()}";

        return Cache::remember($key, now()->addDays(30), function () use ($date) {
            $base = rtrim((string) config('carcare.rate_api.history'), '/');
            $path = $date->format('Y/m/d');
            $rate = $this->extract($this->get("{$base}/{$this->tipo}/{$path}"));

            // Some past dates (weekends/holidays) have no quote; use current
            // as a reasonable fallback rather than storing nothing.
            return $rate ?? $this->current();
        });
    }

    /** Perform the HTTP GET, returning decoded JSON array or null. */
    private function get(string $url): ?array
    {
        try {
            $res = Http::timeout((int) config('carcare.rate_api.timeout', 4))
                ->acceptJson()
                ->get($url);

            return $res->successful() ? (array) $res->json() : null;
        } catch (\Throwable $e) {
            Log::warning('ExchangeRateService fetch failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** Pull a usable rate (mid of compra/venta) from an API payload. */
    private function extract(?array $data): ?float
    {
        if (! $data) {
            return null;
        }

        // Some endpoints return a list; take the first entry.
        if (array_is_list($data)) {
            $data = $data[0] ?? [];
        }

        $compra = isset($data['compra']) ? (float) $data['compra'] : null;
        $venta = isset($data['venta']) ? (float) $data['venta'] : null;

        $valores = array_filter([$compra, $venta], fn ($v) => $v && $v > 0);

        return $valores ? round(array_sum($valores) / count($valores), 4) : null;
    }
}
