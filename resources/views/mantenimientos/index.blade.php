<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mantenimiento</h2>
            <div class="flex gap-2">
                <a href="{{ route('export.csv', 'mantenimientos') }}"><x-secondary-button>Exportar CSV</x-secondary-button></a>
                <a href="{{ route('mantenimientos.create') }}"><x-primary-button>+ Registrar</x-primary-button></a>
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
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3 text-right">Odómetro</th>
                            <th class="px-4 py-3">Taller</th>
                            <th class="px-4 py-3 text-right">Costo</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($mantenimientos as $m)
                            <tr>
                                <td class="px-4 py-3">{{ $m->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ \App\Models\Mantenimiento::TIPOS[$m->tipo] ?? ucfirst($m->tipo) }}</td>
                                <td class="px-4 py-3 text-right">{{ km($m->odometro) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $m->taller ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ show_money($m->montoArs(), $m->usd_rate) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('mantenimientos.edit', $m) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('mantenimientos.destroy', $m) }}" class="inline"
                                          onsubmit="return confirm('¿Eliminar este registro?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline ml-2">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Sin mantenimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $mantenimientos->links() }}
        </div>
    </div>
</x-app-layout>
