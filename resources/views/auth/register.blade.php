@extends('layouts.guest')

@section('title', 'Cadastro')

@section('content')
    <h4 class="text-center mb-4">Criar conta grátis</h4>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="name" class="form-control" placeholder="Nome completo" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="E-mail" value="{{ old('email') }}" required>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Senha (mín. 8 caracteres)" required>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmar senha" required>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('password')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-success w-100">Cadastrar</button>
    </form>

    <p class="mb-0 mt-3">
        Já tem conta? <a href="{{ route('login') }}">Entrar</a>
    </p>
@endsection
