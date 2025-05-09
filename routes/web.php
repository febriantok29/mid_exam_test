<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

/**
 * Public Routes
 * These routes are accessible without authentication
 */
Route::get('/', function () {
    return view('welcome');
})->name('home');

/**
 * Guest Routes
 * These routes are only accessible for non-authenticated users
 */
Route::middleware('guest')->group(function () {
    // Authentication Routes
    Route::controller(AuthController::class)->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('/register', 'showRegistrationForm')->name('register');
        Route::post('/register', 'register');
    });
});

/**
 * Authenticated Routes
 * These routes require user authentication
 */
Route::middleware('auth')->group(function () {
    // Logout Route
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Books Management
    Route::prefix('books')->name('books.')->controller(BookController::class)->group(function () {
        // Public Access Routes
        Route::get('/', 'index')->name('index');
        
        // Admin Only Routes
        Route::middleware(AdminMiddleware::class)->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{book}/edit', 'edit')->name('edit');
            Route::put('/{book}', 'update')->name('update');
            Route::delete('/{book}', 'destroy')->name('destroy');
        });

        // This route must be last as it has a dynamic parameter
        Route::get('/{book}', 'show')->name('show');
    });

    // Borrowing Management
    Route::prefix('borrowings')->name('borrowings.')->controller(BorrowingController::class)
        ->group(function () {
            // Static routes first
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/history', 'history')->name('history');
            Route::post('/', 'store')->name('store');

            // Dynamic routes (with parameters) last
            Route::get('/{borrowing}', 'show')->name('show');
            Route::put('/{borrowing}/return', 'returnBook')->name('return');
            Route::put('/{borrowing}/extend', 'extendBorrowing')->name('extend');
        });
});

/**
 * Admin Routes
 * These routes require both authentication and admin privileges
 */
Route::middleware(['auth', AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->controller(AdminController::class)
    ->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/members', 'members')->name('members');
        Route::get('/borrowings', 'borrowings')->name('borrowings');
        Route::get('/books', 'books')->name('books');
        Route::get('/reports', 'reports')->name('reports');
        Route::get('/reports/popular', 'popularBooks')->name('reports.popular');
    });