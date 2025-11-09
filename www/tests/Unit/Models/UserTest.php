<?php

declare(strict_types=1);

use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;

describe('User Model', function () {
    test('can create a user', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com')
            ->and($user->id)->toBeString();
    });

    test('uses UUID for primary key', function () {
        $user = User::factory()->create();

        expect($user->id)->toBeString()
            ->and($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    test('hashes password automatically', function () {
        $user = User::factory()->create([
            'password' => 'plain-text-password',
        ]);

        expect($user->password)->not->toBe('plain-text-password')
            ->and(Hash::check('plain-text-password', $user->password))->toBeTrue();
    });

    test('hides password from array', function () {
        $user = User::factory()->create();
        $array = $user->toArray();

        expect($array)->not->toHaveKey('password');
    });

    test('hides remember_token from array', function () {
        $user = User::factory()->create();
        $array = $user->toArray();

        expect($array)->not->toHaveKey('remember_token');
    });

    test('casts email_verified_at as datetime', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('can create unverified user', function () {
        $user = User::factory()->unverified()->create();

        expect($user->email_verified_at)->toBeNull();
    });

    test('has fillable attributes', function () {
        $fillable = (new User())->getFillable();

        expect($fillable)->toContain('name', 'email', 'password');
    });

    test('name is required', function () {
        expect(fn () => User::factory()->create(['name' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('email is required', function () {
        expect(fn () => User::factory()->create(['email' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('email must be unique', function () {
        User::factory()->create(['email' => 'duplicate@example.com']);

        expect(fn () => User::factory()->create(['email' => 'duplicate@example.com']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('password is required', function () {
        expect(fn () => User::factory()->create(['password' => null]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('has notifiable trait', function () {
        $user = new User();

        expect(method_exists($user, 'notify'))->toBeTrue()
            ->and(method_exists($user, 'notifyNow'))->toBeTrue();
    });

    test('has factory trait', function () {
        expect(User::factory())->toBeInstanceOf(\Illuminate\Database\Eloquent\Factories\Factory::class);
    });
});
