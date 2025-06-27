<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Exception;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;

class AuthService
{
    protected AuthController $authController;

    public function __construct(AuthController $authController)
    {
        $this->authController = $authController;
    }

    /**
     * Attempt to authenticate a user
     */
    public function attemptLogin(string $username, string $password): array
    {
        $request = new Request([
            'username' => $username,
            'password' => $password,
        ]);

        $response = $this->authController->login($request);

        $responseBody = json_decode($response->getContent(), true);

        if (isset($responseBody['error'])) {
            throw new Exception($responseBody['error']);
        }

        $data = $responseBody['data'] ?? [];

        return $data;
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $request = new Request($data);

        $response = $this->authController->register($request);

        $responseBody = json_decode($response->getContent(), true);

        if (isset($responseBody['error'])) {
            throw new Exception($responseBody['error']);
        }

        return $responseBody['data'] ?? [];
    }
}
