<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
    public function index()
    {
        return User::where('email', '!=', 'admin@afc.com.ng')
            ->with('roles:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $user->roles->first()->name ?? $user->role ?? 'staff',
                    'created_at' => $user->created_at,
                ];
            });
    }

    public function update(Request $request, User $user)
    {
        $allowedRoles = ['admin', 'manager', 'staff', 'pos', 'kitchen', 'inventory', 'desk'];
        $data = $request->validate([
            'role' => ['sometimes', 'required', Rule::in($allowedRoles)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('is_active', $data)) {
            $user->is_active = $data['is_active'];
            if ($user->is_active && ! $user->approved_by) {
                $user->approved_by = $request->user()->id;
            }
        }

        if (array_key_exists('role', $data)) {
            $roleName = $data['role'];
            Role::firstOrCreate(['name' => $roleName]);
            $user->role = $roleName;
            $user->syncRoles($roleName);
        }

        $user->save();

        return $user->load('roles');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->noContent();
    }
}
