@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <h4 class="text-center mb-1">Entrar na sua conta</h4>
    <p class="text-muted text-center small mb-4">Acesse seu painel financeiro</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" value="{{ old('email') }}" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Lembrar de mim</label>
            </div>
            <a href="{{ route('password.request') }}" class="small text-decoration-none">Esqueci minha senha</a>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Entrar
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Ainda não tem conta?
        <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Criar nova conta</a>
    </p>
@endsection
