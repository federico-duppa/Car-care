<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Combustible</h2>
            <div class="flex gap-2">
                <a href="{{ route('export.csv', 'combustible') }}"><x-secondary-button>Exportar CSV</x-secondary-button></a>
                <a href="{{ route('combustible.create') }}"><x-primary-button>+ Cargar</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat label="Consumo promedio"
                        :value="$stats->consumoPromedioL100() ? number_format($stats->consumoPromedioL100(), 2, ',', '.').' L/100km' : '—'" />
                <x-stat label="Último consumo"
                        :value="$stats->consumoUltimoL100() ? number_format($stats->consumoUltimoL100(), 2, ',', '.').' L/100km' : '—'" />
                <x-stat label="Nafta / litro (prom.)"
                        :value="$stats->precioLitroPromedio() !== null ? money_active($stats->precioLitroPromedio()) : '—'" />
                <x-stat label="Total combustible" :value="money_active($stats->totalCombustible())" />
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg divide-y">
                @forelse($cargas as $c)
                    <div class="p-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 sm:flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="font-medium text-gray-900">
                                    {{ $c->fecha->format('d/m/Y') }}
                                    @if($c->tanque_lleno)<span class="text-xs text-emerald-600" title="Tanque lleno">· lleno</span>@endif
                                </span>
                                <span class="font-semibold text-gray-900 sm:hidden">{{ show_money($c->montoArs(), $c->usdRate(current_usd_tipo())) }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ km($c->odometro) }} · {{ number_format($c->litros, 2, ',', '.') }} L
                                @if($c->precio_litro !== null) · {{ show_money($c->precio_litro, $c->usdRate(current_usd_tipo())) }}/L @endif
                            </div>
                        </div>

                        <div class="hidden sm:block sm:w-32 sm:text-right font-medium text-gray-900">
                            {{ show_money($c->montoArs(), $c->usdRate(current_usd_tipo())) }}
                        </div>

                        <div class="flex gap-4 text-sm sm:justify-end sm:w-32">
                            <a href="{{ route('combustible.edit', $c) }}" class="text-indigo-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('combustible.destroy', $c) }}"
                                  onsubmit="return confirm('¿Eliminar esta carga?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">Sin cargas registradas.</div>
                @endforelse
            </div>

            {{ $cargas->links() }}
        </div>
    </div>
</x-app-layout>
