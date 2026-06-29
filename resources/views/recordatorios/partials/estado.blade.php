@php
    /** @var \App\Models\Recordatorio $r — must carry the computed `aviso` array. */
    $a = $r->aviso;
    [$badgeClass, $badgeText] = match ($a['estado']) {
        'vencido' => ['bg-red-100 text-red-700', 'Vencido'],
        'proximo' => ['bg-amber-100 text-amber-700', 'Próximo'],
        default => ['bg-emerald-100 text-emerald-700', 'Al día'],
    };

    $partes = [];
    if ($a['restanteKm'] !== null) {
        $partes[] = $a['restanteKm'] <= 0
            ? 'pasado por '.number_format(abs($a['restanteKm']), 0, ',', '.').' km'
            : 'faltan '.number_format($a['restanteKm'], 0, ',', '.').' km';
    }
    if ($a['restanteDias'] !== null && $a['proximaFecha']) {
        $fecha = $a['proximaFecha']->format('d/m/Y');
        $partes[] = match (true) {
            $a['restanteDias'] < 0 => 'venció hace '.abs($a['restanteDias']).' días · '.$fecha,
            $a['restanteDias'] === 0 => 'vence hoy · '.$fecha,
            default => 'en '.$a['restanteDias'].' días · '.$fecha,
        };
    }
@endphp

<div class="min-w-0">
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeClass }}">{{ $badgeText }}</span>
        <span class="font-medium text-gray-900 truncate">{{ $r->titulo }}</span>
    </div>
    <div class="text-sm text-gray-500">
        {{ \App\Models\Recordatorio::CLASES[$r->clase] ?? ucfirst($r->clase) }}@if($partes) · {{ implode(' · ', $partes) }}@endif
    </div>
</div>
