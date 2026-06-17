<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0A0F1C">

    {{-- Inertia head (title, meta) --}}
    @inertiaHead

    {{-- Vite assets (CSS + JS React/TypeScript) --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
</head>
<body class="bg-bg-base text-text-high font-body antialiased">
    @inertia
</body>
</html>
