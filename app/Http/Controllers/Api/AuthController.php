<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate(
                [
                    'username' => 'required|string',
                    'password' => 'required|string',
                ],
                [
                    'username.required' => 'Username harus diisi.',
                    'password.required' => 'Password harus diisi.',
                ],
            );

            $user = User::where('username', $credentials['username'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
                return response()->json(
                    [
                        'error' => 'Username atau password salah, silakan coba lagi.',
                    ],
                    401,
                );
            }

            $userData = [
                'id' => $user->member_id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ];

            return response()->json(
                [
                    'message' => 'Berhasil masuk sebagai ' . $user->full_name,
                    'data' => $userData,
                ],
                200,
            );
        } catch (ValidationException $e) {
            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';

            return response()->json(
                [
                    'error' => $errorMessage,
                ],
                422,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'error' => 'Terjadi kesalahan saat masuk: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'username' => 'required|string|max:50|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'full_name' => 'required|string|max:100',
            ]);

            $user = User::create([
                'username' => $validatedData['username'],
                'full_name' => $validatedData['full_name'],
                'password_hash' => Hash::make($validatedData['password']),
                'role' => 'member',
                'status' => 'active',
            ]);

            $userData = [
                'id' => $user->member_id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ];

            return response()->json(
                [
                    'message' => 'Registrasi berhasil, silakan masuk.',
                    'data' => $userData,
                ],
                201,
            );
        } catch (ValidationException $e) {
            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';

            return response()->json(
                [
                    'error' => $errorMessage,
                ],
                422,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'error' => 'Terjadi kesalahan saat registrasi: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
