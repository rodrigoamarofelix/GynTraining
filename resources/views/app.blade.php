<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="GynTraining">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GynTraining">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <title>GynTraining</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (file_exists(public_path('build/registerSW.js')))
        <script src="{{ asset('build/registerSW.js') }}" defer></script>
    @endif
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div id="app"></div>
</body>
</html>
