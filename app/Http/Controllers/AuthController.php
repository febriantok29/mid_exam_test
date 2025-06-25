<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if ($user && Hash::check($credentials['password'], $user->password_hash)) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended($user->isAdmin() ? 'admin/dashboard' : 'dashboard');
        }

        throw ValidationException::withMessages([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:50|unique:members',
            'password' => 'required|string|min:6|confirmed',
            'full_name' => 'required|string|max:100',
        ]);

        // Create the user with proper password handling
        $user = User::create([
            'username' => $validatedData['username'],
            'full_name' => $validatedData['full_name'],
            'password_hash' => Hash::make($validatedData['password']),
            'role' => 'member',
            'status' => 'active',
            'created_at' => now()
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    /**
     * Logout the authenticated user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
