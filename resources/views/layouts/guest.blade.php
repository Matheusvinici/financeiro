<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Meu Financeiro</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>
<body class="login-page bg-body-secondary">
    <div class="login-box">
        <div class="login-logo mb-4">
            <a href="/login" class="text-decoration-none">
                <i class="fa-solid fa-coins text-warning"></i> <b>Meu Financeiro</b>
            </a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
