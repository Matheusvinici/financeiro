@extends('layouts.guest')

@section('title', 'Recuperar senha')

@section('content')
    <h4 class="text-center mb-1">Recuperar senha</h4>
    <p class="text-muted text-center small mb-4">Enviaremos um link de redefinição para seu e-mail</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" value="{{ old('email') }}" required autofocus>
            </div>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="fa-solid fa-paper-plane me-1"></i> Enviar link de redefinição
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        <a href="{{ route('login') }}" class="text-decoration-none">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar para o login
        </a>
    </p>
@endsection
