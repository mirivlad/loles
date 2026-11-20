<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\LoginName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username', '')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', new LoginName(), 'unique:users,username'],
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->assignRole('Student');

        Auth::login($user);

        return redirect('/dashboard');
    }
}
