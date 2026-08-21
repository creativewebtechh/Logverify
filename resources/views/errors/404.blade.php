<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-page antialiased">
    <div class="flex min-h-full items-center justify-center px-4 py-16">
        <div class="w-full max-w-md text-center">
            <a href="{{ url('/') }}" class="inline-flex justify-center focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600">
                <x-logo variant="wide" class="h-9 w-auto" />
            </a>
            <div class="mx-auto mt-8 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <x-icon name="search" class="h-8 w-8" />
            </div>
            <h1 class="mt-6 text-6xl font-bold tracking-tight text-slate-900">404</h1>
            <p class="mt-3 text-lg font-semibold text-slate-900">Page not found</p>
            <p class="mt-1 text-sm text-slate-500">The page you are looking for doesn't exist or has been moved.</p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Go home</a>
                <button type="button" onclick="history.back()" class="inline-flex items-center justify-center rounded-xl border border-slate-100 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Go back</button>
            </div>
        </div>
    </div>
</body>
</html>
