<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('search'));
        $like = '%'.addcslashes($search, '%_\\').'%';

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser->execute($request->validated());

        return redirect()->route('users.index')->with('status', __('User created.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('users.edit', [
            'subject' => $user,
            'roles' => $this->assignableRoles(),
            'canChangeStatus' => $this->canChangeStatus($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->execute($request->user(), $user, $request->validated());

        return redirect()->route('users.index')->with('status', __('User updated.'));
    }

    /**
     * @return list<Role>
     */
    private function assignableRoles(): array
    {
        return array_values(array_filter(
            Role::cases(),
            fn (Role $role) => $role !== Role::SuperAdmin || $this->userHasSuperAdmin(),
        ));
    }

    private function canChangeStatus(User $subject): bool
    {
        $actor = request()->user();

        return $actor
            && $actor->can(Permission::UsersDeactivate->value)
            && $actor->isNot($subject);
    }

    private function userHasSuperAdmin(): bool
    {
        return (bool) request()->user()?->hasRole(Role::SuperAdmin->value);
    }
}
