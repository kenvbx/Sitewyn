<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreUserRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateUserRequest;
use Sitewyn\Core\Base\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('core/base::admin.users.index', [
            'users' => User::query()
                ->with('roles')
                ->withCount('roles')
                ->orderByDesc('is_super_admin')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('core/base::admin.users.create', [
            'user' => new User,
            'roles' => $this->roles(),
            'selectedRoles' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create($this->payload($validated));
        $user->roles()->sync($validated['roles'] ?? []);
        admin_flash()->success(__('User created successfully.'));

        return redirect()
            ->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        return view('core/base::admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles(),
            'selectedRoles' => $user->roles()->pluck('roles.id')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update($this->payload($validated, $user));
        $user->roles()->sync($validated['roles'] ?? []);
        admin_flash()->success(__('User updated successfully.'));

        return redirect()
            ->route('admin.users.edit', $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth('admin')->id() === $user->id) {
            admin_flash()->error(__('You cannot delete your own account.'));

            return redirect()
                ->route('admin.users.index');
        }

        $user->roles()->detach();
        $user->delete();
        admin_flash()->success(__('User deleted successfully.'));

        return redirect()
            ->route('admin.users.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?User $user = null): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_super_admin' => (bool) ($validated['is_super_admin'] ?? false),
        ];

        if (($validated['password'] ?? '') !== '') {
            $payload['password'] = $validated['password'];
        }

        if ($user && auth('admin')->id() === $user->id) {
            $payload['is_active'] = true;
        }

        return $payload;
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles()
    {
        return Role::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }
}
