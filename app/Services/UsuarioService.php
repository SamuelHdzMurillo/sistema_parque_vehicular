<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\AuditService;

final class UsuarioService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function paginate(int $page = 1, ?string $search = null): array
    {
        return $this->users->paginate($page, 15, $search);
    }

    public function find(int $id): ?array
    {
        return $this->users->findById($id);
    }

    public function getFormData(): array
    {
        return [
            'roles' => $this->users->getRoles(),
            'areas' => $this->users->getAreas(),
        ];
    }

    public function create(array $data): ?string
    {
        $passwordService = new PasswordService();
        $error = $passwordService->validateStrength($data['password'] ?? '');
        if ($error !== null) {
            return $error;
        }
        if ($this->users->findByEmail($data['email']) !== null) {
            return 'El correo electrónico ya está registrado.';
        }
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $id = $this->users->create([
            'role_id' => (int) $data['role_id'],
            'area_id' => $data['area_id'] ? (int) $data['area_id'] : null,
            'nombre' => $data['nombre'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'password_hash' => $hash,
        ]);
        AuditService::log('INSERT', 'users', $id, null, ['email' => $data['email']]);
        return null;
    }

    public function update(int $id, array $data): ?string
    {
        $before = $this->users->findById($id);
        if ($before === null) {
            return 'Usuario no encontrado.';
        }
        $this->users->update($id, [
            'role_id' => (int) $data['role_id'],
            'area_id' => $data['area_id'] ? (int) $data['area_id'] : null,
            'nombre' => $data['nombre'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'activo' => isset($data['activo']) ? 1 : 0,
        ]);
        AuditService::log('UPDATE', 'users', $id, $before, $data);
        return null;
    }
}
