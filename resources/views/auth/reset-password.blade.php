@extends('auth.layout')

@section('title', 'Nueva contraseña')

@section('content')
    <h1>Define una nueva contraseña</h1>
    <p class="auth-intro">Confirma tu correo y registra una contraseña nueva para recuperar el acceso.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Nueva contraseña</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Confirmar nueva contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button class="button" type="submit">Guardar nueva contraseña</button>
    </form>
@endsection
