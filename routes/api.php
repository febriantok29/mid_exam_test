<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Api\ApiAdminMiddleware;
use App\Http\Middleware\Api\UserAuthMiddleware;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');

Route::namespace('App\Http\Controllers\Api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', 'AuthController@login');
        Route::post('/register', 'AuthController@register');
    });

    // Book routes
    Route::prefix('books')->group(function () {
        Route::get('/', 'BookController@index');
        Route::get('/{id}', 'BookController@show');

        Route::middleware(ApiAdminMiddleware::class)->group(function () {
            Route::post('/', 'BookController@store');
            Route::put('/{id}', 'BookController@update');
            Route::delete('/{id}', 'BookController@destroy');
        });
    });

    // Borrowing routes
    Route::prefix('borrowings')
        ->middleware(UserAuthMiddleware::class)
        ->group(function () {
            Route::get('/', 'BorrowingController@index');
            Route::post('/', 'BorrowingController@store');
            Route::get('/{id}', 'BorrowingController@show');
            Route::delete('/{id}', 'BorrowingController@destroy');
            Route::post('/return/{id}', 'BorrowingController@update');
        });

    // Admin routes
    Route::prefix('admin')
        ->middleware(ApiAdminMiddleware::class)
        ->group(function () {
            Route::get('/dashboard', 'AdminController@dashboard');
            Route::get('/members', 'AdminController@members');
            Route::get('/borrowings', 'AdminController@borrowings');
            Route::get('/books', 'AdminController@books');
            Route::get('/popular-books', 'AdminController@popularBooks');
        });
});

