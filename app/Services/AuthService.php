<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
    private static ?array $permissionsCache = null;

    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !(bool) $user['activo']) {
            $this->logAccess(null, $email, false, 'usuario_no_encontrado');
            return false;
        }

        if ($user['bloqueado_hasta'] !== null && strtotime($user['bloqueado_hasta']) > time()) {
            $this->logAccess((int) $user['id'], $email, false, 'cuenta_bloqueada');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->users->incrementFailedAttempts((int) $user['id']);
            $attempts = (int) $user['intentos_fallidos'] + 1;
            $max = (int) config('app', 'security.max_login_attempts');
            if ($attempts >= $max) {
                $minutes = (int) config('app', 'security.lockout_minutes');
                $this->users->lockUntil((int) $user['id'], $minutes);
            }
            $this->logAccess((int) $user['id'], $email, false, 'password_incorrecto');
            return false;
        }

        $this->users->resetFailedAttempts((int) $user['id']);
        $this->loginUser($user, $remember);
        $this->logAccess((int) $user['id'], $email, true, null);
        return true;
    }

    public function loginUser(array $user, bool $remember = false): void
    {
        Session::regenerate();
        unset($user['password_hash']);
        $permissions = $this->users->getPermissionsByRole((int) $user['role_id']);
        $user['permissions'] = array_column($permissions, 'slug');
        Session::set('user', $user);
        self::$permissionsCache = $user['permissions'];
        $this->users->updateLastAccess((int) $user['id']);

        $token = bin2hex(random_bytes(32));
        Session::set('session_token', $token);
        $days = (int) config('app', 'session.remember_days');
        $expires = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $rememberHash = $remember ? password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT) : null;

        $this->users->createSession(
            (int) $user['id'],
            $token,
            $rememberHash,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expires
        );

        if ($remember) {
            setcookie('sicv_remember', $token, [
                'expires' => strtotime($expires),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            ]);
        }
    }

    public function logout(): void
    {
        $token = Session::get('session_token');
        if (is_string($token)) {
            $this->users->invalidateSession($token);
        }
        setcookie('sicv_remember', '', time() - 3600, '/');
        Session::destroy();
        self::$permissionsCache = null;
    }

    public function tryRememberLogin(): bool
    {
        $cookie = $_COOKIE['sicv_remember'] ?? null;
        if (!is_string($cookie) || $cookie === '') {
            return false;
        }
        $session = $this->users->findActiveSession($cookie);
        if ($session === null) {
            return false;
        }
        $user = $this->users->findById((int) $session['user_id']);
        if ($user === null || !(bool) $user['activo']) {
            return false;
        }
        $this->loginUser($user, true);
        return true;
    }

    public static function hasPermission(string $permission): bool
    {
        if (self::$permissionsCache !== null) {
            return in_array($permission, self::$permissionsCache, true);
        }
        $user = Session::get('user');
        if (!is_array($user)) {
            return false;
        }
        $perms = $user['permissions'] ?? [];
        return in_array($permission, $perms, true);
    }

    public static function loadPermissions(): void
    {
        $user = Session::get('user');
        if (is_array($user)) {
            self::$permissionsCache = $user['permissions'] ?? [];
        }
    }

    private function logAccess(?int $userId, string $email, bool $success, ?string $reason): void
    {
        $this->users->logAccess(
            $userId,
            $email,
            $success,
            $reason,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }
}
