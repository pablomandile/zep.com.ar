<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Genera el código más corto posible que esté libre.
 *
 * Arranca en el largo mínimo y prueba códigos aleatorios. Si choca varias veces
 * seguidas, el espacio de ese largo se está llenando y pasa al siguiente. Son
 * aleatorios y no secuenciales para que nadie pueda recorrer los links uno por uno.
 */
class ShortCodeGenerator
{
    public function generate(): string
    {
        $alphabet = (string) config('shortener.alphabet');
        $min = (int) config('shortener.min_length');
        $max = (int) config('shortener.max_length');
        $attempts = (int) config('shortener.attempts_per_length');

        for ($length = $min; $length <= $max; $length++) {
            for ($i = 0; $i < $attempts; $i++) {
                $code = $this->random($alphabet, $length);

                if ($this->isAvailable($code)) {
                    return $code;
                }
            }
        }

        throw new RuntimeException('No se pudo generar un código libre.');
    }

    /**
     * Un código está disponible si no está reservado y nadie lo usa
     * (incluidos los links borrados, para que un link viejo no apunte a otro destino).
     */
    public function isAvailable(string $code): bool
    {
        return ! $this->isReserved($code)
            && ! Link::withTrashed()->where('code', $code)->exists();
    }

    public function isReserved(string $code): bool
    {
        $lower = strtolower($code);

        if (in_array($lower, config('shortener.reserved'), true)) {
            return true;
        }

        // Cualquier ruta de la app cuyo primer segmento coincida con el código.
        foreach (Route::getRoutes() as $route) {
            $first = strtolower(explode('/', $route->uri())[0]);

            if ($first !== '' && ! str_starts_with($first, '{') && $first === $lower) {
                return true;
            }
        }

        return false;
    }

    protected function random(string $alphabet, int $length): string
    {
        $last = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $last)];
        }

        return $code;
    }
}
