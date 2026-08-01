@extends('layouts.guest')

@section('title', 'Nova senha')

@section('content')
    <h4 class="text-center mb-4">Definir nova senha</h4>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="E-mail" value="{{ old('email', $request->email) }}" required autofocus>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Nova senha" required>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmar nova senha" required>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('password')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-primary w-100">Redefinir senha</button>
    </form>
@endsection
