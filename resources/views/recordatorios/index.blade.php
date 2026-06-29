<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Avisos y vencimientos</h2>
            <div class="flex gap-2">
                <a href="{{ route('recordatorios.create', ['clase' => 'documento']) }}"><x-secondary-button>+ Documento</x-secondary-button></a>
                <a href="{{ route('recordatorios.create', ['clase' => 'mantenimiento']) }}"><x-primary-button>+ Mantenimiento</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg divide-y">
                @forelse($recordatorios as $r)
                    <div class="p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        @include('recordatorios.partials.estado', ['r' => $r])

                        <div class="flex flex-wrap gap-4 text-sm sm:justify-end shrink-0">
                            @if($r->clase === 'gasto')
                                <form method="POST" action="{{ route('recordatorios.resolver', $r) }}">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">Registrar de nuevo</button>
                                </form>
                            @elseif($r->clase === 'documento')
                                <form method="POST" action="{{ route('recordatorios.resolver', $r) }}"
                                      onsubmit="return confirm('¿Renovar este vencimiento?')">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">Renovar</button>
                                </form>
                            @else
                                <a href="{{ route('mantenimientos.create', ['tipo' => $r->tipo]) }}"
                                   class="text-emerald-600 hover:underline">Registrar</a>
                            @endif

                            @if($r->clase !== 'gasto')
                                <a href="{{ route('recordatorios.edit', $r) }}" class="text-indigo-600 hover:underline">Editar</a>
                            @endif

                            <form method="POST" action="{{ route('recordatorios.destroy', $r) }}"
                                  onsubmit="return confirm('¿Eliminar este recordatorio?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">
                        Sin recordatorios. Agregá uno de mantenimiento (por km/fecha) o un vencimiento de documento.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
