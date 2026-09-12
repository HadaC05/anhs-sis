<?php

namespace App\Models\Concerns;

trait ChecksAccountStatus
{
    public function isAccountActive(): bool
    {
        return ($this->status ?? 'active') !== 'inactive';
    }
}
