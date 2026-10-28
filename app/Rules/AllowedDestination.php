<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rechaza destinos que apuntan al propio acortador (loops) o a otros acortadores (cadenas).
 */
class AllowedDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        $own = array_filter([
            strtolower((string) config('shortener.domain')),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
        ]);

        if (in_array($host, $own, true)) {
            $fail('No se puede acortar un link de este mismo sitio.');

            return;
        }

        if (in_array($host, config('shortener.blocked_hosts'), true)) {
            $fail('Ese link ya es de un acortador. Pegá la URL original.');
        }
    }
}
