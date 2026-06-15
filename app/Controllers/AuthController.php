<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\PasswordService;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly PasswordService $passwords = new PasswordService()
    ) {
    }

    public function loginForm(Request $request): never
    {
        $this->render('auth.login', [], 'layouts.guest');
    }

    public function login(Request $request): never
    {
        $this->validateCsrf($request);
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $remember = (bool) $request->input('remember');

        if ($email === '' || $password === '') {
            $_SESSION['_old'] = $request->all();
            flash('error', 'Ingrese correo y contraseña.');
            $this->redirect('login');
        }

        if (!$this->auth->attempt($email, $password, $remember)) {
            $_SESSION['_old'] = $request->all();
            flash('error', 'Credenciales incorrectas o cuenta bloqueada.');
            $this->redirect('login');
        }

        flash('success', 'Bienvenido al SICV.');
        $this->redirect('dashboard');
    }

    public function logout(Request $request): never
    {
        $this->validateCsrf($request);
        $this->auth->logout();
        flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('login');
    }

    public function forgotForm(Request $request): never
    {
        $this->render('auth.forgot', [], 'layouts.guest');
    }

    public function forgot(Request $request): never
    {
        $this->validateCsrf($request);
        $email = trim((string) $request->input('email', ''));
        $this->passwords->requestReset($email);
        flash('success', 'Si el correo existe, recibirá instrucciones para restablecer su contraseña.');
        $this->redirect('login');
    }

    public function resetForm(Request $request, string $token): never
    {
        $this->render('auth.reset', ['token' => $token], 'layouts.guest');
    }

    public function reset(Request $request): never
    {
        $this->validateCsrf($request);
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        if ($password !== $confirm) {
            flash('error', 'Las contraseñas no coinciden.');
            $this->redirect('reset-password/' . $token);
        }

        $error = $this->passwords->resetWithToken($token, $password);
        if ($error !== null) {
            flash('error', $error);
            $this->redirect('reset-password/' . $token);
        }

        flash('success', 'Contraseña actualizada. Ya puede iniciar sesión.');
        $this->redirect('login');
    }

    public function changePasswordForm(Request $request): never
    {
        $this->render('auth.change-password');
    }

    public function changePassword(Request $request): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $error = $this->passwords->changePassword(
            $userId,
            (string) $request->input('current_password', ''),
            (string) $request->input('password', '')
        );

        if ($error !== null) {
            flash('error', $error);
            $this->redirect('change-password');
        }

        flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('dashboard');
    }
}
