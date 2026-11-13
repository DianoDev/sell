<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function actingAsConfirmedAdmin(): User
{
    $admin = User::factory()->admin()->create();

    test()->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);

    return $admin;
}

test('only administrators pass the admin gate', function () {
    expect(Gate::forUser(User::factory()->admin()->make())->allows('access-admin'))->toBeTrue();
    expect(Gate::forUser(User::factory()->make())->allows('access-admin'))->toBeFalse();
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('users without administrator access cannot reach user management', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('administrators must confirm their password first', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.users.index'))
        ->assertRedirect(route('password.confirm'));
});

test('administrators can list users without exposing sensitive attributes', function () {
    actingAsConfirmedAdmin();
    User::factory()->count(2)->create();

    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 3)
            ->has('users.data.0', fn (Assert $user) => $user
                ->hasAll(['id', 'name', 'email', 'is_admin', 'created_at'])
            )
        );
});

test('the list can be filtered by name', function () {
    actingAsConfirmedAdmin();
    User::factory()->create(['name' => 'Marina Lopes']);
    User::factory()->create(['name' => 'Otávio Reis']);

    $this->get(route('admin.users.index', ['name' => 'marina']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Marina Lopes')
            ->where('query.name', 'marina')
        );
});

test('the list rejects sorting and page sizes outside the allowed values', function (array $query, string $field) {
    actingAsConfirmedAdmin();

    $this->get(route('admin.users.index', $query))->assertSessionHasErrors($field);
})->with([
    'unknown sort column' => [['sort' => 'password'], 'sort'],
    'unknown direction' => [['direction' => 'sideways'], 'direction'],
    'oversized page' => [['per_page' => 999999], 'per_page'],
]);

test('administrators can create a user', function () {
    actingAsConfirmedAdmin();

    $this->post(route('admin.users.store'), [
        'name' => 'Nova Pessoa',
        'email' => 'Nova@Example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'nova@example.com')->firstOrFail();

    expect($user->name)->toBe('Nova Pessoa');
    expect($user->is_admin)->toBeFalse();
    expect(Hash::check('correct-horse-battery', $user->password))->toBeTrue();
});

test('administrator access is granted only when requested', function () {
    actingAsConfirmedAdmin();

    $this->post(route('admin.users.store'), [
        'name' => 'Outra Admin',
        'email' => 'admin2@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        'is_admin' => true,
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'admin2@example.com')->firstOrFail()->is_admin)->toBeTrue();
});

test('creating a user requires valid data', function () {
    actingAsConfirmedAdmin();
    $existing = User::factory()->create();

    $this->post(route('admin.users.store'), [
        'name' => '',
        'email' => $existing->email,
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors(['name', 'email', 'password']);

    expect(User::count())->toBe(2);
});

test('administrators can update a user and a blank password keeps the current one', function () {
    actingAsConfirmedAdmin();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->put(route('admin.users.update', $user), [
        'name' => 'Nome Atualizado',
        'email' => 'atualizado@example.com',
        'password' => '',
        'password_confirmation' => '',
        'is_admin' => true,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Nome Atualizado');
    expect($user->email)->toBe('atualizado@example.com');
    expect($user->is_admin)->toBeTrue();
    expect($user->password)->toBe($originalPassword);
});

test('administrators can set a new password for a user', function () {
    actingAsConfirmedAdmin();
    $user = User::factory()->create();

    $this->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('brand-new-password', $user->refresh()->password))->toBeTrue();
});

test('administrators cannot revoke their own administrator access', function () {
    $admin = actingAsConfirmedAdmin();

    $this->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_admin' => false,
    ])->assertSessionHasErrors([
        'is_admin' => 'Você não pode remover o seu próprio acesso de administrador.',
    ]);

    expect($admin->refresh()->is_admin)->toBeTrue();
});

test('administrators can delete another user', function () {
    actingAsConfirmedAdmin();
    $user = User::factory()->create();

    $this->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh())->toBeNull();
});

test('administrators cannot delete their own account from user management', function () {
    $admin = actingAsConfirmedAdmin();

    $this->delete(route('admin.users.destroy', $admin))
        ->assertRedirect(route('admin.users.index'));

    expect($admin->fresh())->not->toBeNull();
});

test('users without administrator access cannot change users', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('admin.users.destroy', $target))
        ->assertForbidden();

    expect($target->fresh())->not->toBeNull();
});

test('accounts that already exist become administrators when the column is added', function () {
    $migration = require database_path('migrations/2026_11_12_100001_add_is_admin_to_users_table.php');
    $existing = User::factory()->create();

    $migration->down();
    $migration->up();

    expect($existing->refresh()->is_admin)->toBeTrue();
});
