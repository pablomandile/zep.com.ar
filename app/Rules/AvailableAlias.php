<?php

namespace App\Rules;

use App\Services\ShortCodeGenerator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableAlias implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $generator = app(ShortCodeGenerator::class);

        if ($generator->isReserved((string) $value)) {
            $fail('Ese alias está reservado. Elegí otro.');

            return;
        }

        if (! $generator->isAvailable((string) $value)) {
            $fail('Ese alias ya está en uso. Elegí otro.');
        }
    }
}
