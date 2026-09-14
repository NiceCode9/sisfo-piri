<?php

namespace App\Http\Controllers\Api\Cbt;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string', // NIS / email / username
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['identifier'])
            ->orWhere('username', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Kredensial tidak valid.'],
            ]);
        }

        if (! $user->hasRole('siswa')) {
            throw ValidationException::withMessages([
                'identifier' => ['Akun ini bukan akun siswa.'],
            ]);
        }

        // Hapus token lama supaya tidak numpuk (opsional, tergantung kebijakan multi-device)
        $user->tokens()->where('name', 'cbt-token')->delete();

        $token = $user->createToken('cbt-token', ['cbt:access'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
