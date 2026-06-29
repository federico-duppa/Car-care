<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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

            <div class="bg-white shadow-sm sm:rounded-lg divide-y">
                @forelse($gastos as $g)
                    <div class="p-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 sm:flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="font-medium text-gray-900">
                                    {{ \App\Models\Gasto::CATEGORIAS[$g->categoria] ?? ucfirst($g->categoria) }}
                                    @if($g->recurrente)<span title="Recurrente">🔁</span>@endif
                                </span>
                                <span class="font-semibold text-gray-900 sm:hidden">{{ show_money($g->montoArs(), $g->usdRate(current_usd_tipo())) }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $g->fecha->format('d/m/Y') }}@if($g->descripcion) · {{ $g->descripcion }}@endif
                            </div>
                        </div>

                        <div class="hidden sm:block sm:w-32 sm:text-right font-medium text-gray-900">
                            {{ show_money($g->montoArs(), $g->usdRate(current_usd_tipo())) }}
                        </div>

                        <div class="flex gap-4 text-sm sm:justify-end sm:w-32">
                            <a href="{{ route('gastos.edit', $g) }}" class="text-indigo-600 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('gastos.destroy', $g) }}"
                                  onsubmit="return confirm('¿Eliminar este gasto?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">Sin gastos registrados.</div>
                @endforelse
            </div>

            {{ $gastos->links() }}
        </div>
    </div>
</x-app-layout>
