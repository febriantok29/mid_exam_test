<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // Total books and book statistics
        $totalBooks = Book::count();
        $bookStats = [
            'total' => $totalBooks,
            'lowStock' => Book::where('quantity_available', '>', 0)
                            ->where('quantity_available', '<', 3)->count(),
            'outOfStock' => Book::where('quantity_available', 0)->count(),
            'neverBorrowed' => Book::whereDoesntHave('borrowings')->count(),
        ];

        // Get active and overdue borrowings count
        $activeBorrowings = Borrowing::where('status', 'borrowed')->count();
        $overdueBorrowings = Borrowing::where('status', 'borrowed')
                                ->where('return_date', '<', now())->count();

        // Total members
        $totalMembers = User::count();

        // Recent borrowings
        $recentBorrowings = Borrowing::with(['book', 'member'])
                                    ->latest('borrow_date')
                                    ->take(5)
                                    ->get();

        // Recent books
        $recentBooks = Book::latest('created_at')
                          ->paginate(5);

        // Low stock books
        $lowStockBooks = Book::where('quantity_available', '>', 0)
                          ->where('quantity_available', '<', 3)
                          ->get();

        // System notifications
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
    }

    /**
     * Show all members.
     *
     * @return \Illuminate\View\View
     */
    public function members()
    {
        $members = User::paginate(15);
        return view('admin.members', compact('members'));
    }

    /**
     * Show all borrowings.
     *
     * @return \Illuminate\View\View
     */
    public function borrowings()
    {
        $borrowings = Borrowing::with(['book', 'member'])
                            ->latest('borrow_date')
                            ->paginate(20);
        return view('admin.borrowings', compact('borrowings'));
    }

    /**
     * Show the reports page.
     *
     * @return \Illuminate\View\View
     */
    public function reports()
    {
        return view('admin.reports');
    }

    /**
     * Show the books management page.
     *
     * @return \Illuminate\View\View
     */
    public function books()
    {
        $books = Book::paginate(15);
        return view('admin.books', compact('books'));
    }

    /**
     * Show popular books report.
     *
     * @return \Illuminate\View\View
     */
    public function popularBooks()
    {
        $popularBooks = Book::withCount('borrowings')
                        ->orderByDesc('borrowings_count')
                        ->paginate(15);
        return view('admin.reports.popular', compact('popularBooks'));
    }

    /**
     * Get system notifications.
     *
     * @return array
     */
    private function getSystemNotifications()
    {
        $notifications = [];

        // Check for overdue books
        $overdueCount = Borrowing::where('status', 'borrowed')
                                ->where('return_date', '<', now())
                                ->count();
        if ($overdueCount > 0) {
            $notifications[] = [
                'title' => 'Overdue Books',
                'message' => "There are {$overdueCount} overdue books that need attention.",
                'time' => now()->format('Y-m-d H:i')
            ];
        }

        // Check for low stock books
        $lowStockCount = Book::where('quantity_available', '>', 0)
                            ->where('quantity_available', '<', 3)
                            ->count();
        if ($lowStockCount > 0) {
            $notifications[] = [
                'title' => 'Low Stock Alert',
                'message' => "{$lowStockCount} books are running low on available copies.",
                'time' => now()->format('Y-m-d H:i')
            ];
        }

        // Check for out of stock books
        $outOfStockCount = Book::where('quantity_available', 0)->count();
        if ($outOfStockCount > 0) {
            $notifications[] = [
                'title' => 'Out of Stock Alert',
                'message' => "{$outOfStockCount} books are currently out of stock.",
                'time' => now()->format('Y-m-d H:i')
            ];
        }

        return $notifications;
    }
}