<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gastos</h2>
            <div class="flex gap-2">
                <a href="{{ route('export.csv', 'gastos') }}"><x-secondary-button>Exportar CSV</x-secondary-button></a>
                <a href="{{ route('gastos.create') }}"><x-primary-button>+ Registrar</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3">Descripción</th>
                            <th class="px-4 py-3 text-center">Recurrente</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($gastos as $g)
                            <tr>
                                <td class="px-4 py-3">{{ $g->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ \App\Models\Gasto::CATEGORIAS[$g->categoria] ?? ucfirst($g->categoria) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $g->descripcion ?: '—' }}</td>
                                <td class="px-4 py-3 text-center">{{ $g->recurrente ? '🔁' : '·' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ show_money($g->montoArs(), $g->usd_rate) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('gastos.edit', $g) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('gastos.destroy', $g) }}" class="inline"
                                          onsubmit="return confirm('¿Eliminar este gasto?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline ml-2">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin gastos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $gastos->links() }}
        </div>
    </div>
</x-app-layout>
