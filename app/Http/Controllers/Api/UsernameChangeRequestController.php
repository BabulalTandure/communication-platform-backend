<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsernameChange\SubmitUsernameChangeRequest;
use App\Http\Resources\UsernameChangeRequestResource;
use App\Models\User;
use App\Models\UsernameChangeRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsernameChangeRequestController extends Controller
{
    use ApiResponse;

    public function submit(SubmitUsernameChangeRequest $request): JsonResponse
    {
        $user = $request->user();

        $existingPending = UsernameChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return $this->errorResponse('You already have a pending username change request.', null, 422);
        }

        $changeRequest = UsernameChangeRequest::create([
            'user_id' => $user->id,
            'old_username' => $user->username,
            'requested_username' => $request->requested_username,
            'status' => 'pending',
        ]);

        return $this->successResponse(new UsernameChangeRequestResource($changeRequest), 'Username change request submitted successfully.', 201);
    }

    public function myRequests(Request $request): JsonResponse
    {
        $requests = $request->user()->usernameChangeRequests()->latest()->get();

        return $this->successResponse(UsernameChangeRequestResource::collection($requests), 'User username change requests retrieved successfully.');
    }

    public function index(Request $request): JsonResponse
    {
        $query = UsernameChangeRequest::with(['user', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->get();

        return $this->successResponse(UsernameChangeRequestResource::collection($requests), 'Username change requests retrieved successfully.');
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $changeRequest = UsernameChangeRequest::with('user')->find($id);

        if (!$changeRequest) {
            return $this->errorResponse('Username change request not found.', null, 404);
        }

        if ($changeRequest->status !== 'pending') {
            return $this->errorResponse('Request has already been reviewed.', null, 400);
        }

        // Verify requested username is still unique
        $usernameExists = User::where('username', $changeRequest->requested_username)->exists();
        if ($usernameExists) {
            return $this->errorResponse('The requested username is already taken.', null, 422);
        }

        DB::transaction(function () use ($changeRequest, $request) {
            // Update user's actual username
            $user = $changeRequest->user;
            $user->username = $changeRequest->requested_username;
            $user->save();

            // Mark request as approved
            $changeRequest->status = 'approved';
            $changeRequest->reviewed_by = $request->user()->id;
            $changeRequest->reviewed_at = now();
            $changeRequest->save();
        });

        $changeRequest->load(['user', 'reviewer']);

        return $this->successResponse(new UsernameChangeRequestResource($changeRequest), 'Username change request approved successfully.');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $changeRequest = UsernameChangeRequest::with('user')->find($id);

        if (!$changeRequest) {
            return $this->errorResponse('Username change request not found.', null, 404);
        }

        if ($changeRequest->status !== 'pending') {
            return $this->errorResponse('Request has already been reviewed.', null, 400);
        }

        $changeRequest->status = 'rejected';
        $changeRequest->reviewed_by = $request->user()->id;
        $changeRequest->reviewed_at = now();
        $changeRequest->save();

        $changeRequest->load(['user', 'reviewer']);

        return $this->successResponse(new UsernameChangeRequestResource($changeRequest), 'Username change request rejected successfully.');
    }
}
