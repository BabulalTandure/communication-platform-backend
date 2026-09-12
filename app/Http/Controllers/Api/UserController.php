<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->get();

        return $this->successResponse(UserResource::collection($users), 'Users retrieved successfully.');
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'status' => $request->status ?? 'active',
            'created_by' => $request->user()->id,
        ]);

        return $this->successResponse(new UserResource($user), 'User created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', null, 404);
        }

        return $this->successResponse(new UserResource($user), 'User details retrieved successfully.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', null, 404);
        }

        $user->status = $request->status;
        $user->save();

        if ($user->status === 'disabled') {
            $user->tokens()->delete();
        }

        return $this->successResponse(new UserResource($user), 'User status updated successfully.');
    }
}
