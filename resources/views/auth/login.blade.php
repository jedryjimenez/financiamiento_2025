{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(to right, #1e3c72, #2a5298);
        font-family: 'Segoe UI', sans-serif;
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-card {
        background-color: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 400px;
    }

    .login-card h4 {
        margin-bottom: 1.5rem;
        font-weight: bold;
        text-align: center;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: #2a5298;
    }

    .btn-login {
        background-color: #2a5298;
        border: none;
    }

    .btn-login:hover {
        background-color: #1e3c72;
    }
</style>

<div class="login-container">
    <div class="login-card">
        <h4>Bienvenido</h4>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Correo</label>
                <input type="email" id="email" name="email" class="form-control"
                       required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control"
                       required>
            </div>

            <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">
                    Recordarme
                </label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-login text-white">Iniciar Sesión</button>
            </div>
        </form>
    </div>
</div>
@endsection
