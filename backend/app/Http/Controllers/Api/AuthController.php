<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\UserResource;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $loginUserAction,
        private readonly LogoutUserAction $logoutUserAction,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $session = $this->loginUserAction->execute($request->validated());
        } catch (AuthenticationException $exception) {
            return ApiResponse::error($exception->getMessage(), 401);
        }

        return ApiResponse::success([
            'user' => UserResource::make($session['user'])->resolve(),
            'token' => $session['token'],
        ], 'Đăng nhập thành công.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => UserResource::make($request->user())->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutUserAction->execute($request->user());

        return ApiResponse::success([], 'Đăng xuất thành công.');
    }
}
