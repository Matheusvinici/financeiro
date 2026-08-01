@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <h4 class="text-center mb-4">Entrar na sua conta</h4>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="E-mail" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Senha" required>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <div class="row">
            <div class="col-8">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Lembrar de mim</label>
                </div>
            </div>
            <div class="col-4">
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </div>
        </div>
    </form>

    <p class="mb-1 mt-3">
        <a href="{{ route('password.request') }}">Esqueci minha senha</a>
    </p>
    <p class="mb-0">
        <a href="{{ route('register') }}" class="text-center">Criar nova conta</a>
    </p>
@endsection
