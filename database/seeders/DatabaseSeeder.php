<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\User;
use App\Services\ShortCodeGenerator;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para desarrollo local. No correr en producción.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => config('shortener.admin_email') ?: 'admin@zep.test',
        ]);

        User::factory()->pending()->create(['name' => 'Ana Pendiente', 'email' => 'ana@example.com']);
        User::factory()->rejected()->create(['name' => 'Rocío Rechazada', 'email' => 'rocio@example.com']);
        $user = User::factory()->create(['name' => 'Usuario Demo', 'email' => 'demo@example.com']);

        $destinos = [
            'https://laravel.com/docs/13.x/routing#route-parameters',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://github.com/pablomandile/zep.com.ar',
            'https://www.argentina.gob.ar/justicia/derechofacil/leysimple/datos-personales',
        ];

        foreach ($destinos as $i => $url) {
            $link = Link::factory()->for($admin)->create([
                'code' => $i === 0 ? 'docs' : app(ShortCodeGenerator::class)->generate(),
                'original_url' => $url,
                'is_custom' => $i === 0,
            ]);

            $this->clicks($link, [12, 40, 3, 0][$i]);
        }

        Link::factory()->for($admin)->inactive()->create(['code' => 'off', 'original_url' => 'https://ejemplo.com/viejo']);
        Link::factory()->for($admin)->expired()->create(['code' => 'promo', 'original_url' => 'https://ejemplo.com/promo']);
        Link::factory()->for($user)->create(['code' => 'demo', 'original_url' => 'https://ejemplo.com/demo']);
    }

    private function clicks(Link $link, int $perDayMax): void
    {
        $referers = ['google.com', 't.co', 'instagram.com', 'facebook.com', null, null];
        $browsers = ['Chrome', 'Chrome', 'Safari', 'Firefox', 'Edge'];
        $os = ['Windows', 'AndroidOS', 'iOS', 'OS X'];
        $countries = ['AR', 'AR', 'AR', 'UY', 'ES', 'MX', null];
        $total = 0;

        for ($d = 29; $d >= 0; $d--) {
            for ($n = rand(0, $perDayMax); $n > 0; $n--) {
                $link->clicks()->create([
                    'clicked_at' => now()->subDays($d)->setTime(rand(0, 23), rand(0, 59)),
                    'referer_host' => fake()->randomElement($referers),
                    'device' => fake()->randomElement(['mobile', 'mobile', 'desktop', 'tablet']),
                    'browser' => fake()->randomElement($browsers),
                    'os' => fake()->randomElement($os),
                    'country' => fake()->randomElement($countries),
                    'ip_hash' => hash('sha256', (string) rand(1, 400)),
                ]);
                $total++;
            }
        }

        $link->forceFill(['clicks_count' => $total, 'last_clicked_at' => $total ? now()->subHours(2) : null])->save();
    }
}
