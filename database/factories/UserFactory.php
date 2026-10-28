<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'status' => UserStatus::Approved,
            'approved_at' => now(),
            'is_admin' => false,
        ];
    }

    /**
     * Cuenta aprobada (es el estado por defecto; existe para que los tests lo expliciten).
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Cuenta recién registrada, esperando aprobación.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Pending,
            'approved_at' => null,
        ]);
    }

    /**
     * Cuenta rechazada o revocada por el admin.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Rejected,
            'approved_at' => null,
        ]);
    }

    /**
     * Administrador.
     */
    public function admin(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
