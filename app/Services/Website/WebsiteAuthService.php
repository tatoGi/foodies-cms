<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebsiteAuthService
{
    /**
     * @param  array{name:string,email:string,phone:string,address:string,password:string}  $data
     * @return array{token:string,user:array<string,mixed>}
     */
    public function register(array $data): array
    {
        $token = $this->plainToken();

        $user = User::query()->create([
            'name' => trim((string) $data['name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => trim((string) $data['phone']),
            'address' => trim((string) $data['address']),
            'password' => (string) $data['password'],
            'remember_token' => Str::random(100),
        ]);

        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return [
            'token' => $token,
            'user' => $this->userPayload($user),
        ];
    }

    /**
     * @param  array{email:string,password:string}  $data
     * @return array{token:string,user:array<string,mixed>}
     */
    public function login(array $data): array
    {
        $user = User::query()
            ->where('email', strtolower(trim((string) $data['email'])))
            ->first();

        if (! $user instanceof User || ! Hash::check((string) $data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $token = $this->plainToken();
        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return [
            'token' => $token,
            'user' => $this->userPayload($user),
        ];
    }

    /**
     * @param  array{name:string,email:string,phone:string,address:string}  $data
     * @return array<string, mixed>
     */
    public function updateProfile(User $user, array $data): array
    {
        $user->update([
            'name' => trim((string) $data['name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => trim((string) $data['phone']),
            'address' => trim((string) $data['address']),
        ]);

        return $this->userPayload($user->fresh() ?? $user);
    }

    public function logout(User $user): void
    {
        $user->forceFill([
            'api_token' => null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'address' => (string) ($user->address ?? ''),
        ];
    }

    private function plainToken(): string
    {
        return Str::random(60);
    }
}
