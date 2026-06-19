<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $base = parse_url((string) config('app', 'url'), PHP_URL_PATH) ?: '';
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($uri, '/');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $_POST;
    }

    public function file(string $key): ?array
    {
        $files = $this->files($key);
        return $files[0] ?? null;
    }

    /** @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}> */
    public function files(string $key): array
    {
        $file = $_FILES[$key] ?? null;
        if ($file === null) {
            return [];
        }
        if (!is_array($file['name'])) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            return [$file];
        }

        $normalized = [];
        foreach ($file['name'] as $i => $name) {
            if (($file['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $file['type'][$i],
                'tmp_name' => $file['tmp_name'][$i],
                'error' => $file['error'][$i],
                'size' => $file['size'][$i],
            ];
        }
        return $normalized;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function bearerToken(): ?string
    {
        return null;
    }
}
