<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Carbon;
use Throwable;

trait NormalizesInput
{
    /**
     * Como en goo.gl: "ejemplo.com/algo" se toma como https://ejemplo.com/algo.
     */
    protected function normalizeUrl(mixed $value): string
    {
        $url = trim((string) $value);

        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    /**
     * Pasa una fecha con zona (el frontend manda ISO en UTC) a la zona de la app.
     * Eloquent guarda la hora tal cual viene, sin convertirla: sin esto un
     * vencimiento quedaría corrido. Si no se puede leer, se deja para que falle
     * la validación.
     */
    protected function normalizeDate(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return $value;
        }
    }
}
