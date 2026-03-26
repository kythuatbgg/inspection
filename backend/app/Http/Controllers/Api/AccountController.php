<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = Account::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $accounts,
        ]);
    }
}
