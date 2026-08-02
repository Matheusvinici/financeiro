<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Meu Financeiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @stack('styles')

    <script>
        (function () {
            const tema = localStorage.getItem('mf-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', tema);
        })();
    </script>
</head>
<body data-bs-theme="light">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <a href="{{ route('login') }}" class="logo-icon text-decoration-none"><i class="fa-solid fa-coins"></i></a>
                <span class="logo-text">Meu Financeiro</span>
            </div>
            @yield('content')
        </div>
    </div>
    @stack('scripts')
</body>
</html>
