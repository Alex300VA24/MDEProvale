@extends('auth.layout')

@section('title', 'Verificar correo')

@section('content')
    <h1>Verifica tu correo electrónico</h1>
    <p class="auth-intro">Antes de continuar, abre el enlace que enviamos a tu correo. Si no lo recibiste, puedes solicitar uno nuevo.</p>

    <div class="actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="button" type="submit">Reenviar enlace de verificación</button>
        </form>
        <form class="secondary-action" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="link-button" type="submit">Cerrar sesión</button>
        </form>
    </div>
@endsection
