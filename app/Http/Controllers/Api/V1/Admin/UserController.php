<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private const SORTS = [
        'newest' => ['id', 'desc'],
        'oldest' => ['id', 'asc'],
        'name' => ['name', 'asc'],
    ];

    public function index(Request $request): JsonResponse
    {
        [$sortColumn, $sortDirection] = self::SORTS[$request->input('sort')] ?? self::SORTS['newest'];

        $users = User::query()
            ->withCount('appointments')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(
                    fn ($q) => $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                );
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->value()))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->integer('per_page', 15));

        return $this->success(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('appointments')->load([
            'appointments' => fn ($query) => $query->with(['service', 'doctor'])->latest('date')->limit(5),
        ]);

        return $this->success(new UserResource($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        // The model's `hashed` cast hashes the password on assignment.
        $user = User::create($request->validated());

        return $this->success(new UserResource($user->loadCount('appointments')), 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($request->user()->is($user) && $request->filled('role') && $request->input('role') !== 'admin') {
            return $this->error('You cannot remove your own admin access.', 422);
        }

        $user->update($request->validated());

        return $this->success(new UserResource($user->loadCount('appointments')));
    }

    /**
     * Deleting a user with appointment history would null out those
     * records' account link (appointments.user_id is nullOnDelete) — refuse
     * so booking history stays attributable. Tokens are revoked explicitly
     * since personal_access_tokens has no foreign key to cascade from.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->error('You cannot delete your own account.', 422);
        }

        if ($user->appointments()->exists()) {
            return $this->error('This user has appointment history and cannot be deleted.', 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->success();
    }
}
