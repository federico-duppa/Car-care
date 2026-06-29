@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
