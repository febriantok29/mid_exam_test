<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\AdminController;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Member dashboard
Route::get('/dashboard', function () {
    $recentBooks = Book::latest()->take(5)->get();
    return view('dashboard', compact('recentBooks'));
})->middleware('auth')->name('dashboard');

// Book routes
Route::middleware(['auth'])->group(function () {
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    
    // Admin-only book management routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
    });

    // These routes should come after more specific routes
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
});

// Borrowing routes
Route::middleware(['auth'])->group(function () {
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/create', [BorrowingController::class, 'create'])->name('borrowings.create');
    Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::get('/borrowings/{borrowing}', [BorrowingController::class, 'show'])->name('borrowings.show');
    Route::put('/borrowings/{borrowing}/return', [BorrowingController::class, 'returnBook'])->name('borrowings.return');
    Route::put('/borrowings/{borrowing}/extend', [BorrowingController::class, 'extendBorrowing'])->name('borrowings.extend');
    Route::get('/history', [BorrowingController::class, 'history'])->name('borrowings.history');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/borrowings', [AdminController::class, 'borrowings'])->name('admin.borrowings');
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/books', [AdminController::class, 'books'])->name('admin.books');
    Route::get('/reports/popular', [AdminController::class, 'popularBooks'])->name('admin.reports.popular');
});
