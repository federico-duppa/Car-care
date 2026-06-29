<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tablero</h2>
            @isset($vehiculo)
                <span class="text-sm text-gray-500">{{ $vehiculo->nombre }} · {{ km($vehiculo->km_actual) }}</span>
            @endisset
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            @if(! $vehiculo)
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                    <p class="text-gray-600 mb-4">Todavía no cargaste ningún vehículo.</p>
                    <a href="{{ route('vehiculos.create') }}">
                        <x-primary-button>Agregar mi auto</x-primary-button>
                    </a>
                </div>
            @else
                {{-- Reminders / upcoming due --}}
                @if($avisos->isNotEmpty())
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-800">Próximos vencimientos</h3>
                            <a href="{{ route('recordatorios.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
                        </div>
                        <div class="divide-y">
                            @foreach($avisos as $r)
                                <div class="py-2 first:pt-0 last:pb-0">
                                    @include('recordatorios.partials.estado', ['r' => $r])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Stat cards --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-stat label="Consumo promedio"
                            :value="$stats->consumoPromedioL100() ? number_format($stats->consumoPromedioL100(), 2, ',', '.').' L/100km' : '—'"
                            sub="Método tanque lleno" />
                    <x-stat label="Costo por km"
                            :value="$stats->costoPorKm() !== null ? money_active($stats->costoPorKm()) : '—'"
                            sub="Todo incluido" />
                    <x-stat label="Nafta / litro (prom.)"
                            :value="$stats->precioLitroPromedio() !== null ? money_active($stats->precioLitroPromedio()) : '—'" />
                    <x-stat label="Distancia registrada"
                            :value="km($stats->distanciaRecorrida())" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <x-stat label="Total combustible" :value="money_active($stats->totalCombustible())" />
                    <x-stat label="Total mantenimiento" :value="money_active($stats->totalMantenimiento())" />
                    <x-stat label="Total otros gastos" :value="money_active($stats->totalGastos())" />
                </div>

                <div class="bg-indigo-600 text-white shadow-sm sm:rounded-lg p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-indigo-100 text-sm">
                            Gasto total del vehículo
                            @if(current_currency() === 'USD' && ($usdActual ?? null))
                                <span class="text-indigo-200">· hoy {{ $usdTipo ?? 'blue' }} ${{ number_format($usdActual, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        <div class="text-3xl font-bold break-words">{{ money_active($stats->totalGeneral()) }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('combustible.create') }}"><x-secondary-button>+ Combustible</x-secondary-button></a>
                        <a href="{{ route('gastos.create') }}"><x-secondary-button>+ Gasto</x-secondary-button></a>
                    </div>
                </div>

                {{-- Monthly spend --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Gasto mensual (últimos 6 meses)</h3>
                    @php $maxMes = max(array_map('floatval', $gastoMensual)) ?: 1; @endphp
                    <div class="space-y-2">
                        @foreach($gastoMensual as $mes => $total)
                            <div class="flex items-center gap-3">
                                <span class="w-16 text-xs text-gray-500">{{ $mes }}</span>
                                <div class="flex-1 bg-gray-100 rounded h-5">
                                    <div class="bg-indigo-500 h-5 rounded" style="width: {{ round($total / $maxMes * 100) }}%"></div>
                                </div>
                                <span class="w-32 text-right text-sm text-gray-700">{{ money_active($total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {{-- Spend by category --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Gastos por categoría</h3>
                        @forelse($gastosPorCategoria as $cat => $total)
                            <div class="flex justify-between py-1 border-b last:border-0 text-sm">
                                <span class="text-gray-600">{{ \App\Models\Gasto::CATEGORIAS[$cat] ?? ucfirst($cat) }}</span>
                                <span class="font-medium">{{ money_active($total) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">Sin gastos cargados.</p>
                        @endforelse
                    </div>

                    {{-- Recent maintenance --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">Últimos mantenimientos</h3>
                        @forelse($ultimosMantenimientos as $m)
                            <div class="flex justify-between py-1 border-b last:border-0 text-sm">
                                <span class="text-gray-600">{{ \App\Models\Mantenimiento::TIPOS[$m->tipo] ?? ucfirst($m->tipo) }}
                                    <span class="text-gray-400">· {{ $m->fecha->format('d/m/Y') }}</span></span>
                                <span class="font-medium">{{ show_money($m->montoArs(), $m->usdRate(current_usd_tipo())) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">Sin mantenimientos cargados.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
