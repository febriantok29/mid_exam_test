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
        try {
            $validatedData = $request->validate([
                'username' => 'required|string|max:50|unique:members',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|string|matches_password:password',
                'full_name' => 'required|string|max:100',
            ], [
                'username.unique' => 'Username sudah digunakan.',
                'password_confirmation.matches_password' => 'Konfirmasi kata sandi tidak cocok.',
                'full_name.required' => 'Nama lengkap harus diisi.',
            ]);

            // Make sure password_confirmation is included in data sent to service
            $registrationData = $validatedData;
            if (!isset($registrationData['password_confirmation']) && $request->has('password_confirmation')) {
                $registrationData['password_confirmation'] = $request->input('password_confirmation');
            }

            // Use service to register
            $userData = $this->authService->register($registrationData);

            // Get user for web auth
            $user = User::find($userData['id']);
            Auth::login($user);

            return redirect('/dashboard');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['username' => 'Terjadi kesalahan saat registrasi: ' . $e->getMessage()]);
        }
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
