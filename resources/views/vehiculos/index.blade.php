<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vehículos</h2>
            <a href="{{ route('vehiculos.create') }}" class="self-start"><x-primary-button>+ Agregar</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            @forelse($vehiculos as $v)
                <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900">
                            {{ $v->nombre }}
                            @if($v->anio) <span class="text-gray-400 font-normal">· {{ $v->anio }}</span> @endif
                            @if(optional($vehiculoActivo)->id === $v->id)
                                <span class="ml-2 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Activo</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $v->patente ?: 'Sin patente' }} · {{ km($v->km_actual) }}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if(optional($vehiculoActivo)->id !== $v->id)
                            <form method="POST" action="{{ route('vehiculos.activar', $v) }}">
                                @csrf
                                <x-secondary-button>Usar</x-secondary-button>
                            </form>
                        @endif
                        <a href="{{ route('vehiculos.edit', $v) }}"><x-secondary-button>Editar</x-secondary-button></a>
                        <form method="POST" action="{{ route('vehiculos.destroy', $v) }}"
                              onsubmit="return confirm('¿Eliminar este vehículo y todos sus registros?')">
                            @csrf @method('DELETE')
                            <x-danger-button>Eliminar</x-danger-button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    No hay vehículos. Agregá el primero.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
