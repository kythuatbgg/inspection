<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function execute(array $credentials): array
    {
        $username = mb_strtolower(trim((string) ($credentials['username'] ?? '')));

        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [$username])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new AuthenticationException('Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        $user->tokens()->delete();
        $token = $user->createToken('inspection-session')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
