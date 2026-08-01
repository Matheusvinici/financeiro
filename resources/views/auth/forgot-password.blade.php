@extends('layouts.guest')

@section('title', 'Recuperar senha')

@section('content')
    <h4 class="text-center mb-4">Recuperar senha</h4>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="E-mail cadastrado" value="{{ old('email') }}" required autofocus>
        </div>
        @error('email')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-primary w-100">Enviar link de redefinição</button>
    </form>

    <p class="mb-0 mt-3">
        <a href="{{ route('login') }}">Voltar para o login</a>
    </p>
@endsection
