<?php

namespace App\Models\Concerns;

use App\Models\Student;

trait ResolvesAccountRole
{
    public function isStudent(): bool
    {
        return $this instanceof Student;
    }

    public function roleName(): string
    {
        if ($this->isStudent()) {
            return 'student';
        }

        return $this->role?->role_name ?? '';
    }
}
