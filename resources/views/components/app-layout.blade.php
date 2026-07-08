@props(['title' => 'RIKMS'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - RIKMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <x-sidebar />
    <div class="app-frame">
        <x-topbar />
        <main class="main-content">
            @if (session('status'))
                <div class="notice notice-success">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="notice notice-warning">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice notice-danger">
                    <strong>Please review the form.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
