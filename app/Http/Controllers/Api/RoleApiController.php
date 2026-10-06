<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Roles & permissions (admins only). Admins create roles and tick what each one may do.
 * Admin always keeps every permission so nobody gets locked out; buyer is for shoppers only.
 */
class RoleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->admin($request);

        return response()->json($this->payload());
    }

    public function store(Request $request): JsonResponse
    {
        $this->admin($request);
        $data = $this->validateRole($request);

        DB::transaction(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => true]);
            $role->permissions()->sync($this->permissionIds($data['permissions'] ?? []));
        });
        Role::flushCache();

        return response()->json($this->payload(), 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->admin($request);
        abort_if(in_array($role->name, Role::LOCKED, true), 422, $role->name === 'admin'
            ? 'Admin always has every permission, so nobody gets locked out.'
            : 'Buyers only shop. Give a person a staff role to let them into the portal.');
        $data = $this->validateRole($request, $role);
        if (isset($data['name']) && $data['name'] !== $role->name && in_array($role->name, Role::BUILT_IN, true)) {
            throw ValidationException::withMessages(['name' => 'The Staff role can\'t be renamed. Create a new role instead.']);
        }

        DB::transaction(function () use ($role, $data) {
            $role->update(array_intersect_key($data, array_flip(['name', 'description'])));
            if (array_key_exists('permissions', $data)) {
                $role->permissions()->sync($this->permissionIds($data['permissions']));
            }
        });
        Role::flushCache();

        return response()->json($this->payload());
    }

    /** Only roles nobody has can go, so no account is left without a role. */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->admin($request);
        abort_if(in_array($role->name, Role::BUILT_IN, true), 422, 'Admin, Staff and Buyer are needed by the website and can\'t be deleted.');
        $people = $role->users()->count();
        if ($people > 0) {
            throw ValidationException::withMessages(['role' => "Move its {$people} ".($people === 1 ? 'person' : 'people').' to another role first.']);
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });
        Role::flushCache();

        return response()->json($this->payload());
    }

    private function payload(): array
    {
        $permissions = Permission::where('is_active', true)->orderBy('id')->get();
        $all = $permissions->pluck('name')->all();
        $order = array_flip(array_keys(Permission::LABELS));

        return [
            'roles' => Role::with('permissions:id,name')->withCount('users')->orderBy('id')->get()
                ->sortBy(fn (Role $r) => [['admin' => 0, 'staff' => 1, 'buyer' => 3][$r->name] ?? 2, $r->id]) // Admin, Staff, new roles, Buyer
                ->map(fn (Role $r) => [
                    'id' => $r->id,
                    'name' => Role::label($r->name),
                    'description' => $r->description,
                    'builtIn' => in_array($r->name, Role::BUILT_IN, true),
                    'locked' => in_array($r->name, Role::LOCKED, true),
                    'portal' => $r->name !== 'buyer',
                    'users' => (int) $r->users_count,
                    'permissions' => match ($r->name) {
                        'admin' => $all,
                        'buyer' => [],
                        default => $r->permissions->pluck('name')->intersect($all)->values()->all(),
                    },
                ])->values()->all(),
            'permissions' => $permissions
                ->sortBy(fn (Permission $p) => $order[$p->name] ?? 99)
                ->map(fn (Permission $p) => [
                    'name' => $p->name,
                    'label' => Permission::LABELS[$p->name][0] ?? ucfirst(str_replace('_', ' ', $p->name)),
                    'group' => Permission::LABELS[$p->name][1] ?? 'Other',
                    'description' => $p->description,
                ])->values()->all(),
        ];
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $req = $role ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:40', 'regex:/^[\pL\pN][\pL\pN &\-]*$/u'],
            'description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'permissions' => [$role ? 'sometimes' : 'present', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('is_active', true)],
        ], [
            'name.required' => 'Give the role a name.',
            'name.min' => 'Role names need at least 2 characters.',
            'name.max' => 'Keep role names to 40 characters.',
            'name.regex' => 'Use letters, numbers, spaces, & or - in role names.',
            'permissions.*.exists' => 'One of the ticked permissions doesn\'t exist.',
        ]);

        if (isset($data['name'])) {
            $data['name'] = mb_strtolower(preg_replace('/\s+/', ' ', trim($data['name'])));
            $taken = Role::whereRaw('lower(name) = ?', [$data['name']])->when($role, fn ($q) => $q->whereKeyNot($role->id))->exists();
            if ($taken || ($data['name'] === 'guest')) {
                throw ValidationException::withMessages(['name' => 'There is already a role called "'.Role::label($data['name']).'".']);
            }
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = filled($data['description']) ? trim($data['description']) : null;
        }

        return $data;
    }

    /** @param list<string> $names */
    private function permissionIds(array $names): array
    {
        return Permission::whereIn('name', $names)->pluck('id')->all();
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($user->isAdmin(), 403, 'Only an Admin can change roles and permissions.');

        return $user;
    }
}
