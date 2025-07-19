<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

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
        try {
            $credentials = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            // Use service to authenticate
            $userData = $this->authService->attemptLogin($credentials['username'], $credentials['password']);

            // Get user for web auth
            $user = User::find($userData['id']);
            Auth::login($user);

            return redirect()->intended($user->isAdmin() ? 'admin/dashboard' : 'dashboard');
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'username' => $e->getMessage(),
            ]);
        }
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
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'full_name.required' => 'Nama lengkap harus diisi.',
        ]);

        // Use service to register
        $userData = $this->authService->register($validatedData);

        // Get user for web auth
        $user = User::find($userData['id']);
        Auth::login($user);

        return redirect('/dashboard');
    }

    /**
     * Logout the authenticated user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
