<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Utilities\ApiUtilities;
use Exception;
use App\Exceptions\ValidatorException;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard(Request $request)
    {
        try {
            $totalBooks = Book::count();
            $bookStats = [
                'total' => $totalBooks,
                'low_stock' => Book::where('quantity_available', '>', 0)->where('quantity_available', '<=', 5)->count(),
                'out_of_stock' => Book::where('quantity_available', 0)->count(),
                'never_borrowed' => Book::whereDoesntHave('borrowings')->count(),
            ];

            $borrowingStats = [
                'active' => Borrowing::where('status', 'borrowed')->count(),
                'overdue' => Borrowing::where(function ($query) {
                    $query->where('status', 'borrowed')->whereDate('borrow_date', '<', now()->subDays(14));
                })
                    ->orWhere(function ($query) {
                        $query->where('status', 'returned')->whereRaw('DATEDIFF(return_date, borrow_date) > 14');
                    })
                    ->count(),
                'returned' => Borrowing::where('status', 'returned')->count(),
                'total' => Borrowing::count(),
            ];

            $memberStats = [
                'total' => User::where('role', 'member')->count(),
                'active' => User::where('role', 'member')
                    ->whereHas('borrowings', function ($query) {
                        $query->where('status', 'borrowed');
                    })
                    ->count(),
                'inactive' => User::where('role', 'member')->doesntHave('borrowings')->count(),
            ];

            $recentBorrowings = Borrowing::with(['book', 'member'])
                ->orderBy('borrow_date', 'desc')
                ->take(10)
                ->get();

            $lowStockBooks = Book::where('quantity_available', '>', 0)->where('quantity_available', '<=', 5)->orderBy('quantity_available', 'asc')->take(10)->get();

            return response()->json(
                [
                    'message' => 'Berhasil mengambil data dashboard.',
                    'data' => [
                        'book_stats' => $bookStats,
                        'borrowing_stats' => $borrowingStats,
                        'member_stats' => $memberStats,
                        'recent_borrowings' => $recentBorrowings,
                        'low_stock_books' => $lowStockBooks,
                    ],
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Gagal mengambil data dashboard.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function members(Request $request)
    {
        try {
            $members = User::query();

            if ($request->has('search')) {
                $search = $request->input('search');
                $members->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            }

            // ROle
            if ($request->has('role')) {
                $role = $request->input('role');
                $members->where('role', $role);
            } else {
                $members->where('role', 'member');
            }

            // status
            if ($request->has('status')) {
                $status = $request->input('status');
                $members->where('status', $status);
            }

            if ($request->has('sort_by')) {
                $sortBy = $request->input('sort_by');
                $sortOrder = $request->input('sort_order', 'asc');
                $members->orderBy($sortBy, $sortOrder);
            } else {
                $members->orderBy('created_at', 'desc');
            }

            // Add pagination
            $perPage = $request->get('per_page', 15);
            $result = $members->paginate($perPage);

            return response()->json(
                [
                    'message' => 'Berhasil mengambil data anggota.',
                    'data' => $result->items(),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'from' => $result->firstItem(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'to' => $result->lastItem(),
                        'total' => $result->total(),
                    ],
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Gagal mengambil data anggota.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function borrowings(Request $request)
    {
        try {
            $query = Borrowing::query();

            // Apply member filter
            if ($request->has('member_id')) {
                $query->where('member_id', $request->member_id);
            }

            // Apply book filter
            if ($request->has('book_id')) {
                $query->where('book_id', $request->book_id);
            }

            // Apply status filter
            if ($request->has('status') && in_array($request->status, ['borrowed', 'returned'])) {
                $query->where('status', $request->status);
            }

            // Apply overdue filter
            if ($request->has('overdue') && $request->overdue == 'true') {
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('status', 'borrowed')->whereRaw('DATEDIFF(NOW(), borrow_date) > 14');
                    })->orWhere(function ($q2) {
                        $q2->where('status', 'returned')->whereRaw('DATEDIFF(return_date, borrow_date) > 14');
                    });
                });
            }

            // Apply date range filter
            if ($request->has('start_date')) {
                $query->whereDate('borrow_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('borrow_date', '<=', $request->end_date);
            }

            // Apply sorting
            $sortField = $request->get('sort_by', 'borrow_date');
            $sortDirection = $request->get('sort_direction', 'desc');

            if (!in_array($sortField, ['borrowing_id', 'borrow_date', 'return_date'])) {
                $sortField = 'borrow_date';
            }

            $query->orderBy($sortField, $sortDirection);

            // Add pagination
            $perPage = $request->get('per_page', 15);
            $borrowings = $query->with(['book', 'member'])->paginate($perPage);

            return response()->json(
                [
                    'message' => 'Berhasil mengambil data peminjaman.',
                    'data' => $borrowings->items(),
                    'meta' => [
                        'current_page' => $borrowings->currentPage(),
                        'from' => $borrowings->firstItem(),
                        'last_page' => $borrowings->lastPage(),
                        'per_page' => $borrowings->perPage(),
                        'to' => $borrowings->lastItem(),
                        'total' => $borrowings->total(),
                    ],
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Gagal mengambil data peminjaman.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // books
    public function books(Request $request)
    {
        try {
            $query = Book::query();

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            }

            if ($request->has('availability')) {
                switch ($request->availability) {
                    case 'available':
                        $query->where('quantity_available', '>', 0);
                        break;
                    case 'out_of_stock':
                        $query->where('quantity_available', 0);
                        break;
                    case 'low_stock':
                        $query->where('quantity_available', '>', 0)->where('quantity_available', '<', 3);
                        break;
                }
            }

            // Apply sorting
            $sortField = $request->get('sort_by', 'title');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (!in_array($sortField, ['title', 'author', 'year_published', 'quantity_available'])) {
                $sortField = 'title';
            }

            $query->orderBy($sortField, $sortDirection);

            // Add pagination
            $perPage = $request->get('per_page', 15);
            $books = $query->paginate($perPage);

            return response()->json(
                [
                    'message' => 'Berhasil mengambil data buku.',
                    'data' => $books->items(),
                    'meta' => [
                        'current_page' => $books->currentPage(),
                        'from' => $books->firstItem(),
                        'last_page' => $books->lastPage(),
                        'per_page' => $books->perPage(),
                        'to' => $books->lastItem(),
                        'total' => $books->total(),
                    ],
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Gagal mengambil data buku.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    // popularBooks
    public function popularBooks(Request $request)
    {
        try {
            $query = Book::withCount('borrowings')->orderByDesc('borrowings_count');

            // Apply time period filter
            if ($request->has('period')) {
                $date = null;

                switch ($request->period) {
                    case 'this_month':
                        $date = now()->startOfMonth();
                        break;
                    case 'last_month':
                        $date = now()->subMonth()->startOfMonth();
                        break;
                    case 'last_3_months':
                        $date = now()->subMonths(3);
                        break;
                    case 'last_6_months':
                        $date = now()->subMonths(6);
                        break;
                    case 'this_year':
                        $date = now()->startOfYear();
                        break;
                    case 'last_year':
                        $date = now()->subYear()->startOfYear();
                        break;
                }

                if ($date) {
                    $query = Book::withCount([
                        'borrowings' => function ($query) use ($date) {
                            $query->where('borrow_date', '>=', $date);
                        },
                    ])->orderByDesc('borrowings_count');
                }
            }

            // Apply minimum borrowing count filter
            if ($request->has('min_borrowings')) {
                $query->having('borrowings_count', '>=', $request->min_borrowings);
            }

            $popularBooks = $query->get();

            return response()->json(
                [
                    'message' => 'Berhasil mengambil data buku populer.',
                    'data' => $popularBooks,
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Gagal mengambil data buku populer.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
