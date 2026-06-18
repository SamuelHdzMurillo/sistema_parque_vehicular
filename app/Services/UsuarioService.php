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
        return array_merge(
            $this->users->paginate($page, 15, $search),
            ['roles' => $this->users->getRoles()]
        );
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

    public function createQuick(array $data): int|string
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $apellidoPaterno = trim((string) ($data['apellido_paterno'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($nombre === '') {
            return 'El nombre es obligatorio.';
        }
        if ($apellidoPaterno === '') {
            return 'El apellido paterno es obligatorio.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Ingrese un correo electrónico válido.';
        }
        if ($this->users->findByEmail($email) !== null) {
            return 'El correo electrónico ya está registrado.';
        }

        $roleId = $this->users->findRoleIdBySlug('responsable_vehiculo');
        if ($roleId === null) {
            return 'No se encontró el rol de responsable de vehículo.';
        }

        $areaId = (int) ($data['area_id'] ?? 0);
        $telefono = trim((string) ($data['telefono'] ?? ''));
        $password = bin2hex(random_bytes(8));

        $id = $this->users->create([
            'role_id' => $roleId,
            'area_id' => $areaId > 0 ? $areaId : null,
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => trim((string) ($data['apellido_materno'] ?? '')) ?: null,
            'email' => $email,
            'telefono' => $telefono !== '' ? $telefono : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        AuditService::log('INSERT', 'users', $id, null, ['email' => $email, 'quick' => true]);

        return $id;
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
