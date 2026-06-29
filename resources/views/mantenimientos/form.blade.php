<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $mantenimiento->exists ? 'Editar mantenimiento' : 'Nuevo mantenimiento' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-flash />

                <form method="POST"
                      action="{{ $mantenimiento->exists ? route('mantenimientos.update', $mantenimiento) : route('mantenimientos.store') }}"
                      class="space-y-4">
                    @csrf
                    @if($mantenimiento->exists) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="block mt-1 w-full"
                                          :value="old('fecha', optional($mantenimiento->fecha)->format('Y-m-d') ?? $mantenimiento->fecha)" required />
                        </div>
                        <div>
                            <x-input-label for="tipo" value="Tipo" />
                            <select id="tipo" name="tipo"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach(\App\Models\Mantenimiento::TIPOS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('tipo', $mantenimiento->tipo) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="odometro" value="Odómetro (km)" />
                            <x-text-input id="odometro" name="odometro" type="number" class="block mt-1 w-full"
                                          :value="old('odometro', $mantenimiento->odometro)" />
                        </div>
                        <div>
                            <x-input-label for="costo" value="Costo (ARS)" />
                            <x-text-input id="costo" name="costo" type="number" step="0.01" class="block mt-1 w-full"
                                          :value="old('costo', $mantenimiento->costo)" required />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="taller" value="Taller (opcional)" />
                        <x-text-input id="taller" name="taller" class="block mt-1 w-full"
                                      :value="old('taller', $mantenimiento->taller)" />
                    </div>

                    <div>
                        <x-input-label for="notas" value="Notas" />
                        <textarea id="notas" name="notas" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $mantenimiento->notas) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('mantenimientos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $mantenimiento->exists ? 'Guardar' : 'Registrar' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
