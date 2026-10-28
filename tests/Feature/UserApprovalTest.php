<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Notifications\NewUserPendingApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shortener.admin_email' => 'admin@zep.test']);
    }

    public function test_new_registrations_are_pending_and_the_admin_is_notified()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create(['email' => 'admin@zep.test']);

        $this->post(route('register.store'), [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $ana = User::where('email', 'ana@example.com')->first();
        $this->assertSame(UserStatus::Pending, $ana->status);
        Notification::assertSentTo($admin, NewUserPendingApproval::class);
    }

    public function test_the_admin_email_registers_as_approved_admin()
    {
        $this->post(route('register.store'), [
            'name' => 'Pablo',
            'email' => 'admin@zep.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $user = User::where('email', 'admin@zep.test')->first();
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->isApproved());
    }

    public function test_admin_approves_and_rejects()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.users.approve', $user))->assertRedirect();
        $this->assertTrue($user->refresh()->isApproved());
        $this->assertSame($admin->id, $user->approved_by);
        Notification::assertSentTo($user, AccountApproved::class);

        $user->createToken('app', ['links:read']);
        $this->actingAs($admin)->post(route('admin.users.reject', $user))->assertRedirect();
        $this->assertSame(UserStatus::Rejected, $user->refresh()->status);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_non_admins_cannot_reach_the_admin_panel()
    {
        $user = User::factory()->create();
        $pending = User::factory()->pending()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.approve', $pending))->assertForbidden();
        $this->assertFalse($pending->refresh()->isApproved());
    }

    public function test_admin_sees_the_panel()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_google_login_creates_a_pending_account()
    {
        Notification::fake();
        $this->fakeGoogleUser('g-1', 'nuevo@gmail.com', 'Nuevo');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertSame('g-1', $user->google_id);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_links_an_existing_account()
    {
        $user = User::factory()->create(['email' => 'ya@existe.com']);
        $this->fakeGoogleUser('g-2', 'ya@existe.com', 'Ya Existe');

        $this->get(route('google.callback'));

        $this->assertSame('g-2', $user->refresh()->google_id);
        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_with_the_admin_email_is_approved_admin()
    {
        $this->fakeGoogleUser('g-3', 'admin@zep.test', 'Pablo');

        $this->get(route('google.callback'));

        $user = User::where('email', 'admin@zep.test')->first();
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->isApproved());
    }

    private function fakeGoogleUser(string $id, string $email, string $name): void
    {
        $google = (new GoogleUser)->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar' => 'https://lh3.googleusercontent.com/a/x',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($google);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
