<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the USD/ARS rate (blue or oficial) used as an inflation anchor.
 * Always available — no enable flag. Every network failure degrades to null
 * so saving an expense or rendering a page never breaks.
 */
class ExchangeRateService
{
    /** Latest rate for a quote (mid of buy/sell), or null. */
    public function current(string $tipo = 'blue'): ?float
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        $tipo = $this->normalize($tipo);

        return Cache::remember("usd:current:{$tipo}", now()->addHours(6), function () use ($tipo) {
            $base = rtrim((string) config('carcare.rate_api.current'), '/');

            return $this->extract($this->get("{$base}/{$tipo}"));
        });
    }

    /** Latest rates for every supported quote: ['blue' => x, 'oficial' => y]. */
    public function currentAll(): array
    {
        $out = [];
        foreach ((array) config('carcare.usd_tipos', ['blue']) as $tipo) {
            $out[$tipo] = $this->current($tipo);
        }

        return $out;
    }

    /** Rate on a specific date (historical anchor) for a quote, or null. */
    public function forDate(Carbon|string $date, string $tipo = 'blue'): ?float
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        $tipo = $this->normalize($tipo);
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($date->isToday() || $date->isFuture()) {
            return $this->current($tipo);
        }

        $key = "usd:{$tipo}:{$date->toDateString()}";

        return Cache::remember($key, now()->addDays(30), function () use ($date, $tipo) {
            $base = rtrim((string) config('carcare.rate_api.history'), '/');
            $rate = $this->extract($this->get("{$base}/{$tipo}/{$date->format('Y/m/d')}"));

            // Weekends/holidays have no quote; fall back to the current rate.
            return $rate ?? $this->current($tipo);
        });
    }

    /** Historical rates for every supported quote on a date. */
    public function forDateAll(Carbon|string $date): array
    {
        $out = [];
        foreach ((array) config('carcare.usd_tipos', ['blue']) as $tipo) {
            $out[$tipo] = $this->forDate($date, $tipo);
        }

        return $out;
    }

    private function normalize(string $tipo): string
    {
        $tipos = (array) config('carcare.usd_tipos', ['blue']);

        return in_array($tipo, $tipos, true) ? $tipo : ($tipos[0] ?? 'blue');
    }

    private function get(string $url): ?array
    {
        try {
            $res = Http::timeout((int) config('carcare.rate_api.timeout', 4))
                ->acceptJson()->get($url);

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

        if (array_is_list($data)) {
            $data = $data[0] ?? [];
        }

        $compra = isset($data['compra']) ? (float) $data['compra'] : null;
        $venta = isset($data['venta']) ? (float) $data['venta'] : null;

        $valores = array_filter([$compra, $venta], fn ($v) => $v && $v > 0);

        return $valores ? round(array_sum($valores) / count($valores), 4) : null;
    }
}
