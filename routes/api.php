<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Api\ApiAdminMiddleware;
use App\Http\Middleware\Api\UserAuthMiddleware;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');
