<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Probe - Laravel API Client</title>
    @if (isset($probeStyle))
        <link rel="stylesheet" href="{{ $probeStyle }}">
    @endif
</head>
<body class="h-full overflow-hidden antialiased">
    <div id="app" class="h-full w-full"></div>
    @if (isset($probeScript))
        <script type="module" src="{{ $probeScript }}"></script>
    @endif
</body>
</html>
