<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Search by name or username (Case-insensitive for PostgreSQL compatibility)
        if ($request->has('search') && $request->search) {
            $searchTerm = strtolower($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'message' => 'Lấy danh sách người dùng thành công',
            'data' => $users
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,inspector,staff',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Tạo người dùng thành công',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json([
            'message' => 'Lấy thông tin người dùng thành công',
            'data' => $user
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,manager,inspector,staff',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Cập nhật người dùng thành công',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Không thể xóa tài khoản của chính bạn'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Xóa người dùng thành công'
        ]);
    }

    /**
     * Get user stats.
     */
    public function stats()
    {
        $stats = [
            'total' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'manager' => User::where('role', 'manager')->count(),
            'inspector' => User::where('role', 'inspector')->count(),
            'staff' => User::where('role', 'staff')->count(),
        ];

        return response()->json([
            'message' => 'Lấy thống kê thành công',
            'data' => $stats
        ]);
    }
}
