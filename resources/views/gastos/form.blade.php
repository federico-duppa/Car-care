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
                        <x-input-label for="monto" value="Monto" />
                        <x-text-input id="monto" name="monto" type="number" step="0.01" class="block mt-1 w-full"
                                      :value="old('monto', $gasto->monto)" required />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <x-text-input id="descripcion" name="descripcion" class="block mt-1 w-full"
                                      :value="old('descripcion', $gasto->descripcion)" />
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="recurrente" value="0">
                        <input type="checkbox" name="recurrente" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(old('recurrente', $gasto->recurrente)) >
                        <span class="text-sm text-gray-700">Gasto recurrente (seguro mensual, patente anual…)</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('gastos.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <x-primary-button>{{ $gasto->exists ? 'Guardar' : 'Registrar' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
