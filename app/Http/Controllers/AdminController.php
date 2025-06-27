<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Show the admin dashboard.
     *
     * @return View
     */
    public function dashboard(): View
    {
        try {
            $dashboardData = $this->adminService->getDashboardData();

            // Extract data for the view
            $bookStats = $dashboardData['book_stats'] ?? [];
            $totalBooks = $bookStats['total'] ?? 0;
            $borrowingStats = $dashboardData['borrowing_stats'] ?? [];
            $memberStats = $dashboardData['member_stats'] ?? [];
            $activeBorrowings = $borrowingStats['active'] ?? 0;
            $overdueBorrowings = $borrowingStats['overdue'] ?? 0;
            $totalMembers = $memberStats['total'] ?? 0;            // Convert recent borrowings to collection of objects to ensure object access
            $recentBorrowings = collect($dashboardData['recent_borrowings'] ?? [])->map(function ($borrowing) {
                // Convert main borrowing to object
                $borrowingObj = (object) $borrowing;

                // Convert book to object if it exists
                if (isset($borrowingObj->book) && is_array($borrowingObj->book)) {
                    $borrowingObj->book = (object) $borrowingObj->book;
                }

                // Convert member to object if it exists
                if (isset($borrowingObj->member) && is_array($borrowingObj->member)) {
                    $borrowingObj->member = (object) $borrowingObj->member;
                }

                // Ensure dates are properly formatted as strings
                if (isset($borrowingObj->borrow_date)) {
                    $borrowingObj->borrow_date = date('Y-m-d', strtotime($borrowingObj->borrow_date));
                }

                if (isset($borrowingObj->return_date)) {
                    $borrowingObj->return_date = date('Y-m-d', strtotime($borrowingObj->return_date));
                }

                return $borrowingObj;
            });

            // Convert low stock books to collection of objects to ensure object access
            $lowStockBooks = collect($dashboardData['low_stock_books'] ?? [])->map(function ($book) {
                return (object) $book;
            });

            // Convert low stock books array to a paginator for recent books
            $perPage = 5;
            $currentPage = request()->input('page', 1);
            $recentBooksArray = $lowStockBooks;
            $recentBooks = new LengthAwarePaginator(
                $recentBooksArray->forPage($currentPage, $perPage),
                count($recentBooksArray),
                $perPage,
                $currentPage,
                ['path' => request()->url()]
            );

            // System notifications might need to be generated locally
            $notifications = $this->getSystemNotifications();

            return view('admin.dashboard', compact(
                'totalBooks',
                'bookStats',
                'activeBorrowings',
                'overdueBorrowings',
                'totalMembers',
                'recentBorrowings',
                'recentBooks',
                'lowStockBooks',
                'notifications'
            ));
        } catch (Exception $e) {
            Log::error('Error in admin dashboard: ' . $e->getMessage());

            // Provide default values for variables
            $totalBooks = 0;
            $bookStats = [
                'total' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'never_borrowed' => 0
            ];
            $activeBorrowings = 0;
            $overdueBorrowings = 0;
            $totalMembers = 0;
            $recentBorrowings = collect([]);
            $lowStockBooks = collect([]);

            // Empty paginator for recent books
            $recentBooks = new LengthAwarePaginator(
                collect([]),
                0,
                5,
                1,
                ['path' => request()->url()]
            );
            $notifications = [];

            return view('admin.dashboard', compact(
                'totalBooks',
                'bookStats',
                'activeBorrowings',
                'overdueBorrowings',
                'totalMembers',
                'recentBorrowings',
                'recentBooks',
                'lowStockBooks',
                'notifications'
            ))->with('error', 'Gagal memuat data dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show all members.
     *
     * @return View
     */
    public function members(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 15,
            ];

            $paginatorMembers = $this->adminService->getMembers($filters);

            // Convert array items to objects
            $convertedMembers = collect($paginatorMembers->items())->map(function ($member) {
                return (object) $member;
            });

            // Create a new paginator with converted objects
            $members = new LengthAwarePaginator(
                $convertedMembers,
                $paginatorMembers->total(),
                $paginatorMembers->perPage(),
                $paginatorMembers->currentPage(),
                $paginatorMembers->getOptions()
            );

            return view('admin.members', compact('members'));
        } catch (Exception $e) {
            Log::error('Error in admin members: ' . $e->getMessage());
            $members = new LengthAwarePaginator([], 0, 15);
            return view('admin.members', compact('members'))->with('error', 'Gagal memuat data anggota: ' . $e->getMessage());
        }
    }

    /**
     * Show all borrowings.
     *
     * @return View
     */
    public function borrowings(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'date_range' => $request->input('date_range'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 20,
            ];

            $paginatorBorrowings = $this->adminService->getBorrowings($filters);

            // Get the borrowings as a collection and convert arrays to objects
            $convertedBorrowings = collect($paginatorBorrowings->items())->map(function ($borrowing) {
                // Convert the main borrowing to an object
                $borrowingObj = (object) $borrowing;

                // Convert book to object if it exists
                if (isset($borrowingObj->book) && is_array($borrowingObj->book)) {
                    $borrowingObj->book = (object) $borrowingObj->book;
                }

                // Convert member to object if it exists
                if (isset($borrowingObj->member) && is_array($borrowingObj->member)) {
                    $borrowingObj->member = (object) $borrowingObj->member;
                }

                // Parse dates if they exist
                if (isset($borrowingObj->borrow_date)) {
                    $borrowingObj->borrow_date = \Carbon\Carbon::parse($borrowingObj->borrow_date);
                }

                if (isset($borrowingObj->return_date)) {
                    $borrowingObj->return_date = $borrowingObj->return_date ? \Carbon\Carbon::parse($borrowingObj->return_date) : null;
                }

                return $borrowingObj;
            });

            // Create a new paginator with the converted objects
            $borrowings = new LengthAwarePaginator(
                $convertedBorrowings,
                $paginatorBorrowings->total(),
                $paginatorBorrowings->perPage(),
                $paginatorBorrowings->currentPage(),
                $paginatorBorrowings->getOptions()
            );

            return view('admin.borrowings', compact('borrowings'));
        } catch (Exception $e) {
            Log::error('Error in admin borrowings: ' . $e->getMessage());
            $borrowings = new LengthAwarePaginator(collect([]), 0, 20, 1, ['path' => request()->url()]);
            return view('admin.borrowings', compact('borrowings'))->with('error', 'Gagal memuat data peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Show the reports page.
     *
     * @return View
     */
    public function reports(): View
    {
        return view('admin.reports');
    }

    /**
     * Show the books management page.
     *
     * @return View
     */
    public function books(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 15,
            ];

            $paginatorBooks = $this->adminService->getBooks($filters);

            // Convert array items to objects
            $convertedBooks = collect($paginatorBooks->items())->map(function ($book) {
                return (object) $book;
            });

            // Create a new paginator with converted objects
            $books = new LengthAwarePaginator(
                $convertedBooks,
                $paginatorBooks->total(),
                $paginatorBooks->perPage(),
                $paginatorBooks->currentPage(),
                $paginatorBooks->getOptions()
            );

            return view('admin.books', compact('books'));
        } catch (Exception $e) {
            Log::error('Error in admin books: ' . $e->getMessage());
            $books = new LengthAwarePaginator([], 0, 15);
            return view('admin.books', compact('books'))->with('error', 'Gagal memuat data buku: ' . $e->getMessage());
        }
    }

    /**
     * Show popular books report.
     *
     * @return View
     */
    public function popularBooks(Request $request): View
    {
        try {
            $filters = [
                'period' => $request->input('period'),
                'limit' => $request->input('limit', 15),
            ];

            $booksArray = $this->adminService->getPopularBooks($filters);

            // Convert array items to objects
            $popularBooks = collect($booksArray)->map(function ($book) {
                return (object) $book;
            });

            return view('admin.reports.popular', compact('popularBooks'));
        } catch (Exception $e) {
            Log::error('Error in popular books: ' . $e->getMessage());
            $popularBooks = [];
            return view('admin.reports.popular', compact('popularBooks'))->with('error', 'Gagal memuat data buku populer: ' . $e->getMessage());
        }
    }

    /**
     * Get system notifications based on dashboard data.
     *
     * @return array
     */
    private function getSystemNotifications(): array
    {
        try {
            $dashboardData = $this->adminService->getDashboardData();
            $notifications = [];

            // Extract data for notifications
            $bookStats = $dashboardData['book_stats'] ?? [];
            $borrowingStats = $dashboardData['borrowing_stats'] ?? [];

            // Check for overdue books
            $overdueCount = $borrowingStats['overdue'] ?? 0;
            if ($overdueCount > 0) {
                $notifications[] = [
                    'title' => 'Buku Terlambat',
                    'message' => "Terdapat {$overdueCount} buku yang terlambat dikembalikan.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            // Check for low stock books
            $lowStockCount = $bookStats['low_stock'] ?? 0;
            if ($lowStockCount > 0) {
                $notifications[] = [
                    'title' => 'Stok Buku Menipis',
                    'message' => "{$lowStockCount} buku memiliki stok yang hampir habis.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            // Check for out of stock books
            $outOfStockCount = $bookStats['out_of_stock'] ?? 0;
            if ($outOfStockCount > 0) {
                $notifications[] = [
                    'title' => 'Buku Habis',
                    'message' => "{$outOfStockCount} buku saat ini tidak tersedia.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            return $notifications;
        } catch (Exception $e) {
            Log::warning('Error generating notifications: ' . $e->getMessage());
            return [];
        }
    }
}
