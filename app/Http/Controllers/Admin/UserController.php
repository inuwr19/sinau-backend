<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with('branch')->orderBy('name');

        if ($search = $request->query('q')) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $perPage = (int) $request->query('per_page', 25);
        return $q->paginate($perPage);
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $user->load('branch');
        // hide sensitive fields if needed
        $user->makeHidden(['password', 'remember_token']);
        return $user;
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        // Handle password update only if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Guard: prevent demoting last admin
        if (($user->role === 'admin') && isset($data['role']) && $data['role'] !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot remove the last admin.'], 422);
            }
        }

        $user->update($data);

        $user->makeHidden(['password', 'remember_token']);
        return $user->fresh();
    }

    public function destroy(Request $request, User $user)
    {
        // Prevent deleting self
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => "You can't delete your own account."], 422);
        }

        // Prevent deleting last admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'Cannot delete the last admin.'], 422);
            }
        }

        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
