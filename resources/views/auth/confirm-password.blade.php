@extends('auth.layout')

@section('title', 'Confirmar contraseña')

@section('content')
    <h1>Confirma tu contraseña</h1>
    <p class="auth-intro">Esta operación requiere verificar nuevamente tu identidad antes de continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="field">
            <label for="password">Contraseña actual</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required autofocus>
        </div>
        <button class="button" type="submit">Confirmar y continuar</button>
    </form>
@endsection
