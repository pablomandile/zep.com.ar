<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    public function test_redirects_with_302_and_records_the_click()
    {
        $link = Link::factory()->create(['code' => 'xyz', 'original_url' => 'https://ejemplo.com/una/ruta']);

        $this->get('/xyz', ['User-Agent' => self::BROWSER, 'Referer' => 'https://www.google.com/search'])
            ->assertStatus(302)
            ->assertRedirect('https://ejemplo.com/una/ruta')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $link->refresh();
        $this->assertSame(1, $link->clicks_count);
        $this->assertNotNull($link->last_clicked_at);

        $click = $link->clicks()->first();
        $this->assertSame('google.com', $click->referer_host);
        $this->assertSame('desktop', $click->device);
        $this->assertSame('Chrome', $click->browser);
    }

    public function test_bots_and_link_previews_are_not_counted()
    {
        $link = Link::factory()->create(['code' => 'xyz']);

        $this->get('/xyz', ['User-Agent' => 'WhatsApp/2.23.20.0'])->assertStatus(302);
        $this->get('/xyz', ['User-Agent' => 'facebookexternalhit/1.1'])->assertStatus(302);
        $this->get('/xyz', ['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->assertStatus(302);

        $this->assertSame(0, $link->refresh()->clicks_count);
        $this->assertSame(0, $link->clicks()->count());
    }

    public function test_unknown_code_returns_404()
    {
        $this->get('/noexiste')->assertNotFound();
    }

    public function test_inactive_link_returns_410()
    {
        Link::factory()->inactive()->create(['code' => 'off']);

        $this->get('/off', ['User-Agent' => self::BROWSER])->assertStatus(410);
    }

    public function test_expired_link_returns_410_and_does_not_count()
    {
        $link = Link::factory()->expired()->create(['code' => 'old']);

        $this->get('/old', ['User-Agent' => self::BROWSER])->assertStatus(410);
        $this->assertSame(0, $link->refresh()->clicks_count);
    }

    public function test_app_routes_win_over_codes()
    {
        $this->get('/login')->assertOk();
    }
}
