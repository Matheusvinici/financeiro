<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Meu Financeiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @stack('styles')

    <script>
        (function () {
            const tema = localStorage.getItem('mf-theme') || 'light';
            document.body.setAttribute('data-bs-theme', tema);
        })();
    </script>
</head>
<body data-bs-theme="light">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo-container text-decoration-none">
                <div class="logo-icon"><i class="fa-solid fa-coins"></i></div>
                <span class="logo-text">Meu Financeiro<small>Controle pessoal</small></span>
            </a>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('lancamentos.index') }}" class="nav-link {{ request()->routeIs('lancamentos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wallet"></i><span>Lançamentos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('relatorios.mensal') }}" class="nav-link {{ request()->routeIs('relatorios.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-simple"></i><span>Relatório Mensal</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-tags"></i><span>Categorias</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('cartoes.index') }}" class="nav-link {{ request()->routeIs('cartoes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card"></i><span>Cartões</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('contas-pagar.index') }}" class="nav-link {{ request()->routeIs('contas-pagar.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-hand-holding-dollar"></i><span>Contas a Pagar</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('compartilhamentos.index') }}" class="nav-link {{ request()->routeIs('compartilhamentos.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-share-nodes"></i><span>Compartilhar</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('configuracoes.index') }}" class="nav-link {{ request()->routeIs('configuracoes.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i><span>Configurações</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" id="menuToggle" title="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="header-greeting">
                        <h1>@yield('page-title', 'Dashboard')</h1>
                        <p>Olá, {{ auth()->user()->name }} — {{ \Carbon\Carbon::now()->translatedFormat('l, d \d\e F \d\e Y') }}</p>
                    </div>
                </div>

                <div class="header-actions">
                    <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                        <i class="fa-solid fa-moon" id="themeIcon"></i>
                    </button>
                    <a href="{{ route('configuracoes.index') }}" class="btn-profile d-none d-sm-flex">
                        <i class="fa-solid fa-user"></i>
                        <span>Configurações</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="d-none d-sm-inline">Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="content-area">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex justify-content-between align-items-center" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show d-flex justify-content-between align-items-center" role="alert">
                    <div><strong>Ops!</strong> Verifique os erros abaixo.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>

        <footer class="footer">
            © {{ date('Y') }} Meu Financeiro. Todos os direitos reservados.
        </footer>
    </div>

    <script src="{{ asset('js/chart.umd.min.js') }}"></script>
    @stack('scripts')

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        function aplicarIconeTema(tema) {
            themeIcon.classList.toggle('fa-moon', tema === 'light');
            themeIcon.classList.toggle('fa-sun', tema === 'dark');
        }

        aplicarIconeTema(document.body.getAttribute('data-bs-theme'));

        themeToggle.addEventListener('click', () => {
            const atual = document.body.getAttribute('data-bs-theme');
            const novo = atual === 'dark' ? 'light' : 'dark';
            document.body.setAttribute('data-bs-theme', novo);
            localStorage.setItem('mf-theme', novo);
            aplicarIconeTema(novo);
        });

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 991.98) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>
