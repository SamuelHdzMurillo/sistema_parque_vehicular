<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @param list<string> $errors
     * @param array<string, string> $fieldErrors
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $fieldErrors = [],
    ) {
        parent::__construct($errors[0] ?? 'Datos inválidos.');
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array<string, string> */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }
}
