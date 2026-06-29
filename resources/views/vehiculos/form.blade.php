<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $vehiculo->exists ? 'Editar vehículo' : 'Nuevo vehículo' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-flash />

                <form method="POST"
                      action="{{ $vehiculo->exists ? route('vehiculos.update', $vehiculo) : route('vehiculos.store') }}"
                      class="space-y-4">
                    @csrf
                    @if($vehiculo->exists) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="marca" value="Marca" />
                            <x-text-input id="marca" name="marca" class="block mt-1 w-full"
                                          :value="old('marca', $vehiculo->marca)" required autofocus />
                        </div>
                        <div>
                            <x-input-label for="modelo" value="Modelo" />
                            <x-text-input id="modelo" name="modelo" class="block mt-1 w-full"
                                          :value="old('modelo', $vehiculo->modelo)" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="anio" value="Año" />
                            <x-text-input id="anio" name="anio" type="number" class="block mt-1 w-full"
                                          :value="old('anio', $vehiculo->anio)" />
                        </div>
                        <div>
                            <x-input-label for="patente" value="Patente" />
                            <x-text-input id="patente" name="patente" class="block mt-1 w-full"
                                          :value="old('patente', $vehiculo->patente)" />
                        </div>
                        <div>
                            <x-input-label for="km_actual" value="Km actual" />
                            <x-text-input id="km_actual" name="km_actual" type="number" class="block mt-1 w-full"
                                          :value="old('km_actual', $vehiculo->km_actual)" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notas" value="Notas" />
                        <textarea id="notas" name="notas" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $vehiculo->notas) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('vehiculos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $vehiculo->exists ? 'Guardar' : 'Agregar' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
