<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class PasswordService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function validateStrength(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Debe incluir al menos una mayúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Debe incluir al menos una minúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Debe incluir al menos un número.';
        }
        return null;
    }

    public function changePassword(int $userId, string $current, string $new): ?string
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            return 'Usuario no encontrado.';
        }
        if (!password_verify($current, $user['password_hash'])) {
            return 'La contraseña actual es incorrecta.';
        }
        $error = $this->validateStrength($new);
        if ($error !== null) {
            return $error;
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->users->updatePassword($userId, $hash);
        AuditService::log('UPDATE', 'users', $userId, null, ['password_changed' => true]);
        return null;
    }

    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return;
        }
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->users->createPasswordReset((int) $user['id'], $hash, $expires);
        Session::set('reset_token_debug', $token);
    }

    public function resetWithToken(string $token, string $newPassword): ?string
    {
        $hash = hash('sha256', $token);
        $reset = $this->users->findValidResetToken($hash);
        if ($reset === null) {
            return 'Enlace inválido o expirado.';
        }
        $error = $this->validateStrength($newPassword);
        if ($error !== null) {
            return $error;
        }
        $this->users->updatePassword((int) $reset['user_id'], password_hash($newPassword, PASSWORD_DEFAULT));
        $this->users->markResetUsed((int) $reset['id']);
        return null;
    }
}
