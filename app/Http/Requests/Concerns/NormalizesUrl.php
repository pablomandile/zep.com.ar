<?php

namespace App\Http\Requests\Concerns;

trait NormalizesUrl
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
}
