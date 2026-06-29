<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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

            <div class="bg-white shadow-sm sm:rounded-lg divide-y">
                @forelse($mantenimientos as $m)
                    <div class="p-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 sm:flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="font-medium text-gray-900">{{ \App\Models\Mantenimiento::TIPOS[$m->tipo] ?? ucfirst($m->tipo) }}</span>
                                <span class="font-semibold text-gray-900 sm:hidden">{{ show_money($m->montoArs(), $m->usdRate(current_usd_tipo())) }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $m->fecha->format('d/m/Y') }} · {{ km($m->odometro) }}@if($m->taller) · {{ $m->taller }}@endif
                            </div>
                        </div>

                        <div class="hidden sm:block sm:w-32 sm:text-right font-medium text-gray-900">
                            {{ show_money($m->montoArs(), $m->usdRate(current_usd_tipo())) }}
                        </div>

                        <div class="flex gap-4 text-sm sm:justify-end sm:w-32">
                            <a href="{{ route('mantenimientos.edit', $m) }}" class="text-indigo-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('mantenimientos.destroy', $m) }}"
                                  onsubmit="return confirm('¿Eliminar este registro?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">Sin mantenimientos registrados.</div>
                @endforelse
            </div>

            {{ $mantenimientos->links() }}
        </div>
    </div>
</x-app-layout>
