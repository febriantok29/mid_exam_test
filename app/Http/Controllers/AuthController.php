<?php

namespace App\Http\Controllers;

use App\Models\Member;
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

        // Custom authentication logic since we're using password_hash field
        if (Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'], // Auth system will handle the hashing internally
        ])) {
            $request->session()->regenerate();

            // Check if the authenticated member is an admin
            if (Auth::user()->isAdmin()) {
                return redirect()->intended('admin/dashboard');
            } else {
                return redirect()->intended('dashboard');
            }
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

        // Create the member with proper password handling
        $member = Member::create([
            'username' => $validatedData['username'],
            'password' => $validatedData['password'], // We'll use password instead of password_hash
            'full_name' => $validatedData['full_name'],
            'role' => 'member',
            'status' => 'active',
        ]);

        Auth::login($member);

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
