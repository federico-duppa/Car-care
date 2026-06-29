@props(['label', 'value', 'sub' => null])

<div class="bg-white shadow-sm sm:rounded-lg p-4">
    <div class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</div>
    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</div>
    @if($sub)
        <div class="text-xs text-gray-400 mt-0.5">{{ $sub }}</div>
    @endif
</div>
