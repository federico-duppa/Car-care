<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $recordatorio->exists ? 'Editar recordatorio' : 'Nuevo recordatorio' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-flash />

                <form method="POST"
                      x-data="{ clase: '{{ old('clase', $recordatorio->clase ?? 'mantenimiento') }}' }"
                      action="{{ $recordatorio->exists ? route('recordatorios.update', $recordatorio) : route('recordatorios.store') }}"
                      class="space-y-4">
                    @csrf
                    @if($recordatorio->exists) @method('PUT') @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="clase" value="Tipo de recordatorio" />
                            <select id="clase" name="clase" x-model="clase"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="mantenimiento">Mantenimiento (por km / fecha)</option>
                                <option value="documento">Documento / Vencimiento</option>
                            </select>
                            <x-input-error :messages="$errors->get('clase')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="titulo" value="Título" />
                            <x-text-input id="titulo" name="titulo" class="block mt-1 w-full"
                                          :value="old('titulo', $recordatorio->titulo)" required />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Mantenimiento --}}
                    <div x-show="clase === 'mantenimiento'" x-cloak class="space-y-4">
                        <div>
                            <x-input-label for="tipo_mant" value="Tipo de mantenimiento" />
                            <select id="tipo_mant" name="tipo" x-bind:disabled="clase !== 'mantenimiento'"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach(\App\Models\Mantenimiento::TIPOS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('tipo', $recordatorio->tipo) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Al registrar un mantenimiento de este tipo, el aviso se reinicia solo.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="intervalo_km" value="Cada (km)" />
                                <x-text-input id="intervalo_km" name="intervalo_km" type="number" min="1" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'mantenimiento'"
                                              :value="old('intervalo_km', $recordatorio->intervalo_km)" placeholder="10000" />
                            </div>
                            <div>
                                <x-input-label for="intervalo_meses" value="Cada (meses)" />
                                <x-text-input id="intervalo_meses" name="intervalo_meses" type="number" min="1" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'mantenimiento'"
                                              :value="old('intervalo_meses', $recordatorio->intervalo_meses)" placeholder="12" />
                            </div>
                            <div>
                                <x-input-label for="base_odometro" value="Km de referencia" />
                                <x-text-input id="base_odometro" name="base_odometro" type="number" min="0" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'mantenimiento'"
                                              :value="old('base_odometro', $recordatorio->base_odometro)" />
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('intervalo_km')" class="mt-1" />
                        <x-input-error :messages="$errors->get('intervalo_meses')" class="mt-1" />
                        <p class="text-xs text-gray-500">Indicá al menos km o meses. El "km de referencia" se usa hasta que cargues el primer mantenimiento.</p>
                    </div>

                    {{-- Documento --}}
                    <div x-show="clase === 'documento'" x-cloak class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="tipo_doc" value="Documento" />
                                <select id="tipo_doc" name="tipo" x-bind:disabled="clase !== 'documento'"
                                        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach(\App\Models\Recordatorio::DOCUMENTOS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('tipo', $recordatorio->tipo) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="base_fecha" value="Vence el" />
                                <x-text-input id="base_fecha" name="base_fecha" type="date" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'documento'"
                                              :value="old('base_fecha', optional($recordatorio->base_fecha)->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('base_fecha')" class="mt-1" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="numero" value="N° de póliza / documento (opcional)" />
                                <x-text-input id="numero" name="numero" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'documento'"
                                              :value="old('numero', $recordatorio->numero)" />
                            </div>
                            <div>
                                <x-input-label for="intervalo_meses_doc" value="Renueva cada (meses, opcional)" />
                                <x-text-input id="intervalo_meses_doc" name="intervalo_meses" type="number" min="1" class="block mt-1 w-full"
                                              x-bind:disabled="clase !== 'documento'"
                                              :value="old('intervalo_meses', $recordatorio->intervalo_meses)" placeholder="12" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notas" value="Notas (opcional)" />
                        <textarea id="notas" name="notas" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $recordatorio->notas) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('recordatorios.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $recordatorio->exists ? 'Guardar' : 'Crear' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
