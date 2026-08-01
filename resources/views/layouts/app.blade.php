<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Meu Financeiro</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="fa-solid fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="{{ route('dashboard') }}" class="nav-link">{{ \Carbon\Carbon::now()->translatedFormat('l, d \d\e F \d\e Y') }}</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                        <i class="fa-solid fa-user ms-2"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ route('configuracoes.index') }}"><i class="fa-solid fa-gear me-2"></i>Configurações</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2"></i>Sair</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-body-secondary shadow">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-text fw-bold"><i class="fa-solid fa-coins me-2 text-warning"></i>Meu Financeiro</span>
        </a>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-house"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('lancamentos.index') }}" class="nav-link {{ request()->routeIs('lancamentos.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-wallet"></i><p>Lançamentos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('relatorios.mensal') }}" class="nav-link {{ request()->routeIs('relatorios.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-chart-simple"></i><p>Relatório Mensal</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-tags"></i><p>Categorias</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('cartoes.index') }}" class="nav-link {{ request()->routeIs('cartoes.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-credit-card"></i><p>Cartões</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('contas-pagar.index') }}" class="nav-link {{ request()->routeIs('contas-pagar.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-hand-holding-dollar"></i><p>Contas a Pagar</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('compartilhamentos.index') }}" class="nav-link {{ request()->routeIs('compartilhamentos.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-share-nodes"></i><p>Compartilhar</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('configuracoes.index') }}" class="nav-link {{ request()->routeIs('configuracoes.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-gear"></i><p>Configurações</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="container-fluid">
            <div class="app-content-header">
                <h3 class="mb-0">@yield('page-title', '')</h3>
            </div>
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Ops!</strong> Verifique os erros abaixo.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="app-content mt-2">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Meu Financeiro</div>
        <strong>Copyright &copy; 2026.</strong> Todos os direitos reservados.
    </footer>

</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
@stack('scripts')
</body>
</html>
