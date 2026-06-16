<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with('roles')
            ->when($request->search, fn($qq) => $qq->where('name', 'like', "%{$request->search}%")
                ->orWhere('username', 'like', "%{$request->search}%"))
            ->orderBy('name');

        return response()->json($q->paginate(15));
    }

    public function roles()
    {
        return response()->json(Role::orderBy('name')->get(['id', 'name', 'label']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:4',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'] ?? $data['username'] . '@ortho.local',
            'password' => Hash::make($data['password']),
        ]);
        $user->roles()->sync(Role::whereIn('name', $data['roles'])->pluck('id'));

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:4',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user->fill([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'] ?? $user->email,
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->roles()->sync(Role::whereIn('name', $data['roles'])->pluck('id'));

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
