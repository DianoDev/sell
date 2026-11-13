<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List the users, paginated, sorted and filtered by the validated query.
     */
    public function index(UserIndexRequest $request): Response
    {
        $query = $request->validated();

        $users = User::query()
            ->when($query['name'] ?? null, fn (Builder $builder, string $name) => $builder->whereLike('name', "%{$name}%"))
            ->when($query['email'] ?? null, fn (Builder $builder, string $email) => $builder->whereLike('email', "%{$email}%"))
            ->when(isset($query['is_admin']), fn (Builder $builder) => $builder->where('is_admin', (bool) $query['is_admin']))
            ->orderBy($query['sort'] ?? 'name', $query['direction'] ?? 'asc')
            ->paginate($query['per_page'] ?? 10)
            ->through(fn (User $user) => $user->only(['id', 'name', 'email', 'is_admin', 'created_at']));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'query' => $query,
        ]);
    }

    /**
     * Create a user. Administrator access is assigned explicitly, never mass-assigned.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        $user->is_admin = $request->boolean('is_admin');
        $user->email_verified_at = now();
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuário criado.']);

        return to_route('admin.users.index');
    }

    /**
     * Update a user. A blank password keeps the current one.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($request->has('is_admin')) {
            $user->is_admin = $request->boolean('is_admin');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuário salvo.']);

        return to_route('admin.users.index');
    }

    /**
     * Delete a user. Administrators cannot delete their own account here.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Você não pode excluir a sua própria conta por aqui.']);

            return to_route('admin.users.index');
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuário excluído.']);

        return to_route('admin.users.index');
    }
}
