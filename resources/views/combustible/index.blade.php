<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
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

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3 text-right">Odómetro</th>
                            <th class="px-4 py-3 text-right">Litros</th>
                            <th class="px-4 py-3 text-right">$/litro</th>
                            <th class="px-4 py-3 text-right">Costo</th>
                            <th class="px-4 py-3 text-center">Lleno</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($cargas as $c)
                            <tr>
                                <td class="px-4 py-3">{{ $c->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">{{ km($c->odometro) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($c->litros, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ $c->precio_litro !== null ? show_money($c->precio_litro, $c->usdRate(current_usd_tipo())) : '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ show_money($c->montoArs(), $c->usdRate(current_usd_tipo())) }}</td>
                                <td class="px-4 py-3 text-center">{{ $c->tanque_lleno ? '✓' : '·' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('combustible.edit', $c) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('combustible.destroy', $c) }}" class="inline"
                                          onsubmit="return confirm('¿Eliminar esta carga?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline ml-2">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Sin cargas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $cargas->links() }}
        </div>
    </div>
</x-app-layout>
