@extends('layouts.guest')

@section('title', 'Nova senha')

@section('content')
    <h4 class="text-center mb-1">Definir nova senha</h4>
    <p class="text-muted text-center small mb-4">Escolha uma nova senha para sua conta</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" value="{{ old('email', $request->email) }}" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nova senha</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Confirmar nova senha</label>
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
            <i class="fa-solid fa-key me-1"></i> Redefinir senha
        </button>
    </form>
@endsection
