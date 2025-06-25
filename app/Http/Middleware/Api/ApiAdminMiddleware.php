<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
// User model
use App\Models\User;

class ApiAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->isMethod('get') ? $request->query('user_id') : $request->input('user_id');

        if (!$userId) {
            return response()->json(['error' => 'ID pengguna tidak ditemukan.'], 400);
        }

        $user = User::find($userId);

        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Tidak memiliki izin. Akses admin diperlukan.'], 403);
        }

        return $next($request);
    }
}
