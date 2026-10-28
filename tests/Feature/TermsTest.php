<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTermsAccepted;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class TermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public()
    {
        $this->get(route('terms'))->assertOk();
        $this->get(route('privacy'))->assertOk();
    }

    public function test_registration_requires_accepting_the_terms()
    {
        $this->post(route('register.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
    }

    public function test_registration_records_the_accepted_version()
    {
        $this->post(route('register.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $user = User::where('email', 'ana@example.com')->first();
        $this->assertSame(config('legal.version'), $user->terms_version);
        $this->assertNotNull($user->terms_accepted_at);
    }

    public function test_user_without_acceptance_is_redirected_and_returns_where_it_was_going()
    {
        $user = User::factory()->withoutAcceptedTerms()->create();

        $this->actingAs($user)->get(route('links.index'))->assertRedirect(route('terms.accept'));

        $this->actingAs($user)->post(route('terms.accept.store'))->assertSessionHasErrors('terms');

        $this->actingAs($user)
            ->post(route('terms.accept.store'), ['terms' => '1'])
            ->assertRedirect(route('links.index'));

        $this->assertTrue($user->refresh()->hasAcceptedCurrentTerms());
        $this->actingAs($user)->get(route('links.index'))->assertOk();
    }

    public function test_raising_the_version_asks_again()
    {
        $user = User::factory()->create();
        config(['legal.version' => '2099-01-01']);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('terms.accept'));
        $this->actingAs($user)->get(route('terms.accept'))
            ->assertInertia(fn ($page) => $page->component('auth/AcceptTerms')->where('firstTime', false));
    }

    public function test_without_accepting_can_still_read_logout_delete_and_follow_links()
    {
        $user = User::factory()->withoutAcceptedTerms()->create();
        Link::factory()->create(['code' => 'abc', 'original_url' => 'https://ejemplo.com']);

        $this->actingAs($user)->get(route('terms'))->assertOk();
        $this->actingAs($user)->get('/abc')->assertRedirect('https://ejemplo.com');
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($user);
    }

    public function test_json_without_acceptance_gets_409()
    {
        $user = User::factory()->withoutAcceptedTerms()->create();

        $this->actingAs($user)->getJson(route('links.index'))->assertStatus(409);
    }

    public function test_the_exempt_list_does_not_grow_by_accident()
    {
        $this->assertSame([
            'terms', 'terms.*', 'privacy', 'home', 'logout', 'profile.destroy',
            'verification.*', 'redirect', 'storage.*',
        ], EnsureTermsAccepted::EXEMPT);
    }

    public function test_new_google_account_lands_on_the_acceptance_screen()
    {
        $google = (new GoogleUser)->map(['id' => 'g-9', 'email' => 'g@gmail.com', 'name' => 'G']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($google);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('terms.accept'));
    }

    public function test_deleting_the_account_removes_everything_the_policy_promises()
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        $link->clicks()->create(['clicked_at' => now()]);
        $deleted = Link::factory()->for($user)->create();
        $deleted->delete();
        $user->createToken('app');
        DB::table('sessions')->insert([
            'id' => 'otra-sesion', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);

        $user->delete();

        $this->assertSame(0, Link::withTrashed()->count());
        $this->assertSame(0, DB::table('link_clicks')->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }
}
