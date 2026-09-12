<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\AddGroupMemberRequest;
use App\Http\Requests\Group\CreateGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Chat;
use App\Models\Group;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $groups = Group::with(['members', 'chat'])->latest()->get();

        return $this->successResponse(GroupResource::collection($groups), 'Groups retrieved successfully.');
    }

    public function myGroups(Request $request): JsonResponse
    {
        $groups = $request->user()->groups()->with(['members', 'chat'])->latest()->get();

        return $this->successResponse(GroupResource::collection($groups), 'User groups retrieved successfully.');
    }

    public function store(CreateGroupRequest $request): JsonResponse
    {
        $group = DB::transaction(function () use ($request) {
            $imagePath = null;
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('groups/images', 'public');
            }

            $group = Group::create([
                'name' => $request->name,
                'profile_image' => $imagePath,
                'created_by' => $request->user()->id,
            ]);

            // Create corresponding group chat
            $chat = Chat::create([
                'type' => 'group',
                'group_id' => $group->id,
            ]);

            // Attach creator and initial members
            $userIds = array_unique(array_merge([$request->user()->id], $request->user_ids ?? []));

            $syncData = [];
            foreach ($userIds as $uid) {
                $syncData[$uid] = ['joined_at' => now()];
            }

            $group->members()->sync($syncData);
            $chat->participants()->sync($userIds);

            return $group;
        });

        $group->load(['members', 'chat']);

        return $this->successResponse(new GroupResource($group), 'Group created successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $group = Group::with(['members', 'chat'])->find($id);

        if (!$group) {
            return $this->errorResponse('Group not found.', null, 404);
        }

        return $this->successResponse(new GroupResource($group), 'Group details retrieved successfully.');
    }

    public function update(UpdateGroupRequest $request, int $id): JsonResponse
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->errorResponse('Group not found.', null, 404);
        }

        if ($request->filled('name')) {
            $group->name = $request->name;
        }

        if ($request->hasFile('profile_image')) {
            if ($group->profile_image) {
                Storage::disk('public')->delete($group->profile_image);
            }
            $group->profile_image = $request->file('profile_image')->store('groups/images', 'public');
        }

        $group->save();
        $group->load(['members', 'chat']);

        return $this->successResponse(new GroupResource($group), 'Group updated successfully.');
    }

    public function addMembers(AddGroupMemberRequest $request, int $id): JsonResponse
    {
        $group = Group::with('chat')->find($id);

        if (!$group) {
            return $this->errorResponse('Group not found.', null, 404);
        }

        DB::transaction(function () use ($group, $request) {
            $existingMembers = $group->members()->pluck('users.id')->toArray();
            $newMembers = array_diff($request->user_ids, $existingMembers);

            if (!empty($newMembers)) {
                $syncData = [];
                foreach ($newMembers as $uid) {
                    $syncData[$uid] = ['joined_at' => now()];
                }
                $group->members()->attach($syncData);

                if ($group->chat) {
                    $group->chat->participants()->attach($newMembers);
                }
            }
        });

        $group->load(['members', 'chat']);

        return $this->successResponse(new GroupResource($group), 'Members added to group successfully.');
    }

    public function removeMember(int $id, int $userId): JsonResponse
    {
        $group = Group::with('chat')->find($id);

        if (!$group) {
            return $this->errorResponse('Group not found.', null, 404);
        }

        DB::transaction(function () use ($group, $userId) {
            $group->members()->detach($userId);
            if ($group->chat) {
                $group->chat->participants()->detach($userId);
            }
        });

        $group->load(['members', 'chat']);

        return $this->successResponse(new GroupResource($group), 'Member removed from group successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->errorResponse('Group not found.', null, 404);
        }

        if ($group->profile_image) {
            Storage::disk('public')->delete($group->profile_image);
        }

        $group->delete();

        return $this->successResponse(null, 'Group deleted successfully.');
    }
}
