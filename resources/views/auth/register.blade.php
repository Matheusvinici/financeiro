@extends('layouts.guest')

@section('title', 'Cadastro')

@section('content')
    <h4 class="text-center mb-1">Criar conta grátis</h4>
    <p class="text-muted text-center small mb-4">Comece a controlar suas finanças</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nome completo</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" name="name" class="form-control" placeholder="Seu nome" value="{{ old('name') }}" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" value="{{ old('email') }}" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Confirmar senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Repita a senha" required>
            </div>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('password')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="fa-solid fa-user-plus me-1"></i> Cadastrar
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Já tem conta?
        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Entrar</a>
    </p>
@endsection
