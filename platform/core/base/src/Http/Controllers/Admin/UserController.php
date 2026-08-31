<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreUserRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateUserRequest;
use Sitewyn\Core\Base\Support\DateFilter;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $isActive = $this->activeFilter($request->query('is_active'));
        $createdFrom = $this->dateFilter($request->query('created_from'));
        $createdTo = $this->dateFilter($request->query('created_to'));

        // Outside user management: this surface lists every account that is
        // NOT part of the platform team — not a super admin and no built-in
        // Admin role (negation of User::isTeamMember()). Team members are
        // managed at /admin/system/users (SystemUserController).
        $users = User::query()
            ->where('is_super_admin', false)
            ->whereDoesntHave('roles', fn (Builder $roles) => $roles->where('slug', 'admin'))
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
            ))
            ->when($isActive !== null, fn (Builder $query) => $query->where('is_active', $isActive))
            ->when($createdFrom !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $createdFrom))
            ->when($createdTo !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $createdTo))
            ->orderBy('name')
            ->get();

        return view('core/base::admin.users.index', [
            'users' => $users,
            'search' => $search,
            'isActive' => $isActive,
            'createdFrom' => $createdFrom,
            'createdTo' => $createdTo,
        ]);
    }

    public function create(): View
    {
        return view('core/base::admin.users.create', [
            'user' => new User,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Roles and the super admin flag are not accepted on this surface:
        // outside users can never hold admin privileges.
        User::query()->create($this->payload($request->validated()));
        admin_flash()->success(__('User created successfully.'));

        return redirect()
            ->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        $this->assertOutsideUser($user);

        return view('core/base::admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->assertOutsideUser($user);

        $user->update($this->payload($request->validated(), $user));
        admin_flash()->success(__('User updated successfully.'));

        return redirect()
            ->route('admin.users.edit', $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->assertOutsideUser($user);

        if (Auth::guard('admin')->id() === $user->id) {
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
     * The outside surface never manages team members: they are edited,
     * promoted and deleted at /admin/system/users. Opening a team member
     * here behaves like a missing record.
     */
    private function assertOutsideUser(User $user): void
    {
        if ($user->isTeamMember()) {
            abort(404);
        }
    }

    /**
     * Outside users carry no privileges: only profile fields, activity state
     * and (optionally) a new password. `is_super_admin` is never written —
     * on create it falls back to the column default (false), on update the
     * existing value is left untouched.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?User $user = null): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (($validated['password'] ?? '') !== '') {
            $payload['password'] = $validated['password'];
        }

        if ($user !== null && $this->isEditingSelf($user)) {
            $payload['is_active'] = true;
        }

        return $payload;
    }

    /**
     * The status select submits `0`/`1` strings; anything else counts as no
     * filter so crafted query input never changes the listing semantics.
     */
    private function activeFilter(mixed $isActive): ?int
    {
        return in_array($isActive, ['0', '1'], true) ? (int) $isActive : null;
    }

    /**
     * Query input can be an array (e.g. ?created_from[]=1); narrow it to a
     * string before the shared parser so bad input counts as no filter.
     */
    private function dateFilter(mixed $date): ?string
    {
        return DateFilter::parse(is_string($date) ? $date : null);
    }

    private function isEditingSelf(User $user): bool
    {
        return Auth::guard('admin')->id() === $user->id;
    }
}
