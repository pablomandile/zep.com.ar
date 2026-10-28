<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortenLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_user_can_shorten_a_url()
    {
        $user = User::factory()->approved()->create();

        $this->actingAs($user)
            ->post(route('links.store'), ['url' => 'https://laravel.com/docs'])
            ->assertRedirect(route('dashboard'));

        $link = $user->links()->first();
        $this->assertNotNull($link);
        $this->assertSame('https://laravel.com/docs', $link->original_url);
        $this->assertFalse($link->is_custom);
    }

    public function test_url_without_scheme_gets_https()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('links.store'), ['url' => 'ejemplo.com/algo']);

        $this->assertSame('https://ejemplo.com/algo', $user->links()->first()->original_url);
    }

    public function test_shortening_the_same_url_twice_returns_the_same_link()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://ejemplo.com']);
        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://ejemplo.com']);

        $this->assertSame(1, $user->links()->count());
    }

    public function test_custom_alias_and_expiration()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('links.store'), [
            'url' => 'https://ejemplo.com',
            'alias' => 'promo-2026',
            'expires_at' => now()->addWeek()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $link = $user->links()->first();
        $this->assertSame('promo-2026', $link->code);
        $this->assertTrue($link->is_custom);
        $this->assertNotNull($link->expires_at);
    }

    public function test_alias_must_be_free_and_not_reserved()
    {
        $user = User::factory()->create();
        Link::factory()->create(['code' => 'ocupado']);

        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://a.com', 'alias' => 'ocupado'])
            ->assertSessionHasErrors('alias');
        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://a.com', 'alias' => 'admin'])
            ->assertSessionHasErrors('alias');
        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://a.com', 'alias' => 'con espacio'])
            ->assertSessionHasErrors('alias');
    }

    public function test_rejects_invalid_and_loop_urls()
    {
        $user = User::factory()->create();

        foreach (['', 'javascript:alert(1)', 'ftp://archivo.com/x', 'https://zep.com.ar/abc', 'https://bit.ly/xyz'] as $url) {
            $this->actingAs($user)->post(route('links.store'), ['url' => $url])
                ->assertSessionHasErrors('url');
        }

        $this->assertSame(0, Link::count());
    }

    public function test_pending_user_cannot_shorten_and_is_sent_to_the_waiting_page()
    {
        $user = User::factory()->pending()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('pending'));
        $this->actingAs($user)->post(route('links.store'), ['url' => 'https://a.com'])->assertRedirect(route('pending'));
        $this->actingAs($user)->get(route('pending'))->assertOk();

        $this->assertSame(0, Link::count());
    }

    public function test_users_only_manage_their_own_links()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = Link::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('links.show', $link))->assertForbidden();
        $this->actingAs($other)->patch(route('links.update', $link), ['is_active' => false])->assertForbidden();
        $this->actingAs($other)->delete(route('links.destroy', $link))->assertForbidden();

        $this->actingAs($owner)->get(route('links.show', $link))->assertOk();
        $this->actingAs($owner)->patch(route('links.update', $link), ['is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($link->refresh()->is_active);
    }
}
