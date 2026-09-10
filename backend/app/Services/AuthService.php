<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Public self-registration always creates a Learner account.
     * Admin/Instructor accounts are created by an Admin via UserService::create().
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::Learner,
        ]);

        return [$user, $user->createToken('auth')->plainTextToken];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return [$user, $user->createToken('auth')->plainTextToken];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
