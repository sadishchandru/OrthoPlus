<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Accept either username or email in the username field
        $user = User::where('username', $data['username'])
            ->orWhere('email', $data['username'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = Str::random(64);
        $user->forceFill(['api_token' => $token])->save();

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);
        return response()->json($this->userPayload($user));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->forceFill(['api_token' => null])->save();
        }
        return response()->json(['message' => 'Logged out']);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('roles');
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'email'    => $user->email,
            'roles'    => $user->roles->pluck('name')->values(),
            'role'     => $user->roles->pluck('name')->first(), // primary role for badge
            'page_access' => $user->pageAccess(),               // drives nav + route guard
        ];
    }
}
