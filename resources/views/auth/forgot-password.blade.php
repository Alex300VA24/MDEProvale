@extends('auth.layout')

@section('title', 'Recuperar contraseña')

@section('content')
    <h1>Recuperar contraseña</h1>
    <p class="auth-intro">Ingresa el correo asociado a tu cuenta. Te enviaremos un enlace seguro para establecer una nueva contraseña.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div class="actions">
            <button class="button" type="submit">Enviar enlace de recuperación</button>
            <div class="secondary-action"><a class="link-button" href="{{ route('login') }}">Volver al inicio de sesión</a></div>
        </div>
    </form>
@endsection
