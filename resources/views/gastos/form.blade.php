<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $gasto->exists ? 'Editar gasto' : 'Nuevo gasto' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <x-flash />

                <form method="POST"
                      action="{{ $gasto->exists ? route('gastos.update', $gasto) : route('gastos.store') }}"
                      class="space-y-4">
                    @csrf
                    @if($gasto->exists) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="block mt-1 w-full"
                                          :value="old('fecha', optional($gasto->fecha)->format('Y-m-d') ?? $gasto->fecha)" required />
                        </div>
                        <div>
                            <x-input-label for="categoria" value="Categoría" />
                            <select id="categoria" name="categoria"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach(\App\Models\Gasto::CATEGORIAS as $key => $label)
                                    <option value="{{ $key }}" @selected(old('categoria', $gasto->categoria) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="monto" value="Monto (ARS)" />
                        <x-text-input id="monto" name="monto" type="number" step="0.01" class="block mt-1 w-full"
                                      :value="old('monto', $gasto->monto)" required />
                        <p class="mt-1 text-xs text-gray-500">Ingresá el monto en pesos (ARS). Después podés verlo en USD con el toggle.</p>
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <x-text-input id="descripcion" name="descripcion" class="block mt-1 w-full"
                                      :value="old('descripcion', $gasto->descripcion)" />
                    </div>

                    <div x-data="{ rec: {{ old('recurrente', $gasto->recurrente) ? 'true' : 'false' }} }">
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="recurrente" value="0">
                            <input type="checkbox" name="recurrente" value="1" x-model="rec"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Gasto recurrente (seguro mensual, patente anual…)</span>
                        </label>

                        <div x-show="rec" x-cloak class="mt-3">
                            <x-input-label for="periodicidad_meses" value="Se repite cada (meses)" />
                            <x-text-input id="periodicidad_meses" name="periodicidad_meses" type="number" min="1"
                                          class="block mt-1 w-32" x-bind:disabled="!rec"
                                          :value="old('periodicidad_meses', $gasto->periodicidad_meses ?? 1)" />
                            <p class="mt-1 text-xs text-gray-500">Te avisamos cuando se acerque la próxima y la registrás de nuevo con un clic.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('gastos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $gasto->exists ? 'Guardar' : 'Registrar' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
