<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $carga->exists ? 'Editar carga' : 'Nueva carga de combustible' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-flash />

                <form method="POST"
                      action="{{ $carga->exists ? route('combustible.update', $carga) : route('combustible.store') }}"
                      class="space-y-4">
                    @csrf
                    @if($carga->exists) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="block mt-1 w-full"
                                          :value="old('fecha', optional($carga->fecha)->format('Y-m-d') ?? $carga->fecha)" required />
                        </div>
                        <div>
                            <x-input-label for="odometro" value="Odómetro (km)" />
                            <x-text-input id="odometro" name="odometro" type="number" class="block mt-1 w-full"
                                          :value="old('odometro', $carga->odometro)" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="litros" value="Litros" />
                            <x-text-input id="litros" name="litros" type="number" step="0.01" class="block mt-1 w-full"
                                          :value="old('litros', $carga->litros)" required />
                        </div>
                        <div>
                            <x-input-label for="costo_total" value="Costo total (ARS)" />
                            <x-text-input id="costo_total" name="costo_total" type="number" step="0.01" class="block mt-1 w-full"
                                          :value="old('costo_total', $carga->costo_total)" required />
                        </div>
                    </div>
                    <p class="-mt-2 text-xs text-gray-500">Los montos se ingresan en pesos (ARS).</p>

                    <div>
                        <x-input-label for="estacion" value="Estación (opcional)" />
                        <x-text-input id="estacion" name="estacion" class="block mt-1 w-full"
                                      :value="old('estacion', $carga->estacion)" />
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="tanque_lleno" value="0">
                        <input type="checkbox" name="tanque_lleno" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(old('tanque_lleno', $carga->tanque_lleno ?? true)) >
                        <span class="text-sm text-gray-700">Tanque lleno (necesario para calcular el consumo)</span>
                    </label>

                    <div>
                        <x-input-label for="notas" value="Notas" />
                        <textarea id="notas" name="notas" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $carga->notas) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('combustible.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $carga->exists ? 'Guardar' : 'Registrar' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
