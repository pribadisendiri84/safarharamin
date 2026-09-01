<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $this->authorize('manage-users');

        $query = $this->applyTrashFilter(User::query()->with('creator')->orderBy('name'), $request);

        return view('admin.users.index', [
            'users' => $query->get(),
            'roles' => UserRole::cases(),
            ...$this->trashViewData(User::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-users');

        User::query()->create($this->validated($request));

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('manage-users');

        $data = $this->validated($request, $user);

        if ($this->wouldRemoveLastSuperadmin($user, $data['role'])) {
            return back()->withErrors(['Minimal satu superadmin harus tersisa.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize('manage-users');

        if ($user->is($this->currentUser())) {
            return back()->withErrors(['Tidak bisa menghapus akun sendiri.']);
        }

        if ($this->wouldRemoveLastSuperadmin($user, UserRole::Admin)) {
            return back()->withErrors(['Minimal satu superadmin harus tersisa.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', 'Pengguna dihapus.');
    }

    public function restore(User $user)
    {
        $this->authorize('manage-users');

        $user->restore();

        return redirect()->route('admin.users.index', ['trashed' => 1])->with('ok', 'Pengguna dipulihkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $data['role'] = UserRole::from($data['role']);

        return $data;
    }

    private function wouldRemoveLastSuperadmin(User $user, UserRole $nextRole): bool
    {
        if (! $user->isSuperadmin() || $nextRole === UserRole::Superadmin) {
            return false;
        }

        return ! User::query()
            ->where('role', UserRole::Superadmin)
            ->where('id', '!=', $user->id)
            ->exists();
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
