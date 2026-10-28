<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token()
    {
        $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com'])->assertUnauthorized();
    }

    public function test_creates_a_link()
    {
        Sanctum::actingAs(User::factory()->create(), ['links:read', 'links:write']);

        $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com/largo', 'alias' => 'api-test'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'api-test')
            ->assertJsonPath('data.original_url', 'https://ejemplo.com/largo')
            ->assertJsonPath('data.short_url', Link::shortUrlFor('api-test'));
    }

    public function test_the_same_url_returns_200_with_the_existing_link()
    {
        Sanctum::actingAs(User::factory()->create(), ['links:write']);

        $code = $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com'])->assertCreated()->json('data.code');

        $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com'])
            ->assertOk()
            ->assertJsonPath('data.code', $code);
    }

    public function test_duplicated_alias_is_422()
    {
        Link::factory()->create(['code' => 'tomado']);
        Sanctum::actingAs(User::factory()->create(), ['links:write']);

        $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com', 'alias' => 'tomado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alias');
    }

    public function test_read_only_tokens_cannot_create()
    {
        Sanctum::actingAs(User::factory()->create(), ['links:read']);

        $this->postJson('/api/v1/links', ['url' => 'https://ejemplo.com'])->assertForbidden();
    }

    public function test_pending_users_cannot_use_the_api()
    {
        Sanctum::actingAs(User::factory()->pending()->create(), ['links:read', 'links:write']);

        $this->getJson('/api/v1/links')->assertForbidden();
    }

    public function test_list_show_update_stats_and_delete_own_links()
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create(['code' => 'mio']);
        Link::factory()->create(['code' => 'ajeno']);
        Sanctum::actingAs($user, ['links:read', 'links:write']);

        $this->getJson('/api/v1/links')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/links/mio')->assertOk()->assertJsonPath('data.code', 'mio');
        $this->getJson('/api/v1/links/ajeno')->assertNotFound();

        $this->patchJson('/api/v1/links/mio', ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->getJson('/api/v1/links/mio/stats')
            ->assertOk()
            ->assertJsonPath('data.total_clicks', 0)
            ->assertJsonCount(30, 'data.per_day');

        $this->deleteJson('/api/v1/links/mio')->assertNoContent();
        $this->assertSoftDeleted($link);
    }

    public function test_rate_limit()
    {
        config(['shortener.rate_limits.api_per_minute' => 2]);
        Sanctum::actingAs(User::factory()->create(), ['links:read']);

        $this->getJson('/api/v1/links')->assertOk();
        $this->getJson('/api/v1/links')->assertOk();
        $this->getJson('/api/v1/links')->assertTooManyRequests();
    }
}
