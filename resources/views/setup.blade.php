<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Foundation Setup — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen text-gray-900 antialiased">
    <div class="max-w-2xl px-4 py-12 mx-auto sm:px-6">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight">Foundation Setup</h1>
            <p class="mt-1 text-sm text-gray-500">Choose which features are active in your application.</p>
        </div>

        @if(session('status'))
            <div class="px-4 py-3 mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('foundation.setup.update') }}" class="overflow-hidden bg-white border border-gray-200 rounded-xl">
            @csrf
            <ul class="divide-y divide-gray-100">
                @foreach($features as $feature => $enabled)
                    <li class="flex items-center justify-between px-6 py-4">
                        <div>
                            <span class="text-sm font-semibold capitalize">{{ $feature }}</span>
                            @if($feature === 'auth')
                                <span class="ml-2 text-xs text-gray-400">(foundational — always on)</span>
                            @elseif(!empty($depends[$feature]))
                                <span class="ml-2 text-xs text-gray-400">requires: {{ implode(', ', $depends[$feature]) }}</span>
                            @endif
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="features[{{ $feature }}]" value="1"
                                @checked($enabled)
                                @disabled($feature === 'auth')
                                class="w-5 h-5 rounded text-gray-900 border-gray-300 focus:ring-gray-900">
                        </label>
                    </li>
                @endforeach
            </ul>
            <div class="px-6 py-4 bg-gray-50">
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Save changes
                </button>
            </div>
        </form>

        <p class="mt-6 text-xs text-center text-gray-400">
            Visible in your local environment, or to users granted the <code>viewFoundationSetup</code> ability.
            Changes take effect on the next request.
        </p>
    </div>
</body>
</html>
