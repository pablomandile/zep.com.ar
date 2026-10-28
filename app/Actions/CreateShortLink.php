<?php

namespace App\Actions;

use App\Models\Link;
use App\Models\User;
use App\Services\ShortCodeGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class CreateShortLink
{
    public function __construct(private ShortCodeGenerator $generator) {}

    /**
     * Crea el link corto. Si el usuario ya había acortado la misma URL sin alias
     * ni vencimiento y sigue activa, devuelve esa en vez de crear otra.
     *
     * @param  array{url: string, alias?: string|null, expires_at?: string|null}  $data
     * @return array{0: Link, 1: bool} el link y si se creó ahora
     */
    public function handle(User $user, array $data): array
    {
        $alias = $data['alias'] ?? null;
        $expiresAt = filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null;

        if ($alias === null && $expiresAt === null) {
            $existing = $user->links()
                ->where('url_hash', Link::hashUrl($data['url']))
                ->where('is_custom', false)
                ->whereNull('expires_at')
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if ($existing) {
                return [$existing, false];
            }
        }

        // El índice único es la garantía final: si dos pedidos generan el mismo
        // código al mismo tiempo, el segundo reintenta con otro.
        for ($attempt = 0; ; $attempt++) {
            try {
                $link = $user->links()->create([
                    'code' => $alias ?? $this->generator->generate(),
                    'original_url' => $data['url'],
                    'is_custom' => $alias !== null,
                    'is_active' => true,
                    'expires_at' => $expiresAt,
                ]);

                return [$link, true];
            } catch (UniqueConstraintViolationException $e) {
                if ($alias !== null || $attempt >= 3) {
                    throw $e;
                }
            }
        }
    }
}
