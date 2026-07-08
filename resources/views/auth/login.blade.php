<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - RIKMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-card">
        <div class="brand-row login-brand">
            <div class="brand-mark">R</div>
            <div class="brand-name">RIKMS</div>
        </div>
        <h1>Agency Administrator Login</h1>
        <p>Use the seeded demo account to open the RIKMS prototype.</p>
        <form method="POST" action="{{ route('login.store') }}" class="form-stack">
            @csrf
            <label>
                <span>Email</span>
                <input name="email" type="email" value="{{ old('email', 'test@example.com') }}" required autofocus>
            </label>
            <label>
                <span>Password</span>
                <input name="password" type="password" value="password" required>
            </label>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
            <button class="btn-primary" type="submit">Log in</button>
        </form>
    </main>
</body>
</html>
