<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BorrowingController extends Controller
{
    /**
     * Display a listing of the borrowings
     */
    public function index()
    {
        $borrowings = Borrowing::with(['book', 'member'])
            ->when(!Auth::user()->isAdmin(), function ($query) {
                return $query->where('member_id', Auth::id());
            })
            ->orderBy('borrow_date', 'desc')
            ->paginate(10);

        return view('borrowings.index', compact('borrowings'));
    }

    /**
     * Show the form for borrowing a new book
     */
    public function create(Request $request)
    {
        // Handle the case where book_id is passed in the URL
        if ($request->has('book_id')) {
            $book = Book::findOrFail($request->book_id);
            return view('borrowings.create', compact('book'));
        }

        // Otherwise show all available books
        $availableBooks = Book::where('quantity_available', '>', 0)->get();
        return view('borrowings.create', compact('availableBooks'));
    }

    /**
     * Process a book borrowing
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'book_id' => 'required|exists:books,book_id',
            'borrow_date' => 'required|date|before_or_equal:today',
            'accept_terms' => 'required|accepted',
        ], [
            'borrow_date.before_or_equal' => 'Tanggal peminjaman tidak boleh di masa depan',
            'accept_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan peminjaman'
        ]);

        $book = Book::findOrFail($validatedData['book_id']);

        // Check if book is available
        if ($book->quantity_available <= 0) {
            return redirect()->back()
                ->with('error', 'Buku ini tidak tersedia untuk dipinjam.');
        }

        DB::transaction(function () use ($book, $validatedData) {
            // Create borrowing record
            Borrowing::create([
                'member_id' => Auth::id(),
                'book_id' => $book->book_id,
                'borrow_date' => $validatedData['borrow_date'],
                'status' => 'borrowed'
            ]);

            // Decrement quantity available
            $book->decrement('quantity_available');
        });

        return redirect()->route('borrowings.index')
            ->with('success', 'Buku berhasil dipinjam.');
    }

    /**
     * Display borrowing details
     */
    public function show(Borrowing $borrowing)
    {
        // Make sure user can only view their own borrowings or admin can view all
        if (!Auth::user()->isAdmin() && $borrowing->member_id != Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('borrowings.show', compact('borrowing'));
    }

    /**
     * Return a borrowed book
     */
    public function returnBook(Borrowing $borrowing)
    {
        // Make sure user can only return their own borrowings or admin can return any
        if (!Auth::user()->isAdmin() && $borrowing->member_id != Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if book is already returned
        if ($borrowing->status === 'returned') {
            return redirect()->back()
                ->with('error', 'Buku ini sudah dikembalikan.');
        }

        DB::transaction(function () use ($borrowing) {
            // Update borrowing record
            $borrowing->update([
                'return_date' => now(),
                'status' => 'returned'
            ]);

            // Increment quantity available
            $borrowing->book->increment('quantity_available');
        });

        return redirect()->back()
            ->with('success', 'Buku berhasil dikembalikan.');
    }

    /**
     * Show history of borrowings
     */
    public function history(Request $request)
    {
        $query = Borrowing::with(['book', 'member'])
            ->when(!Auth::user()->isAdmin(), function ($query) {
                return $query->where('member_id', Auth::id());
            });

        // Apply filters if any
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('book', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            switch ($request->status) {
                case 'returned':
                    $query->where('status', 'returned');
                    break;
                case 'borrowed':
                    $query->where('status', 'borrowed');
                    break;
                case 'overdue':
                    $query->where(function($q) {
                        $q->where(function($q2) {
                            // Peminjaman yang masih dipinjam dan terlambat
                            $q2->where('status', 'borrowed')
                               ->whereDate('borrow_date', '<', now()->subDays(14));
                        })->orWhere(function($q2) {
                            // Peminjaman yang sudah dikembalikan tapi terlambat
                            $q2->where('status', 'returned')
                               ->whereRaw('DATEDIFF(return_date, borrow_date) > 14');
                        });
                    });
                    break;
            }
        }

        if ($request->has('date_range')) {
            $now = Carbon::now();

            switch ($request->date_range) {
                case 'last_month':
                    // Bulan lalu: dari awal sampai akhir bulan lalu
                    $startDate = $now->copy()->subMonth()->startOfMonth();
                    $endDate = $now->copy()->subMonth()->endOfMonth();
                    $query->whereBetween('borrow_date', [$startDate, $endDate]);
                    break;

                case 'last_3_months':
                    // 3 bulan terakhir: dari 3 bulan lalu sampai hari ini
                    $startDate = $now->copy()->subMonths(3)->startOfDay();
                    $query->whereBetween('borrow_date', [$startDate, $now]);
                    break;

                case 'last_6_months':
                    // 6 bulan terakhir: dari 6 bulan lalu sampai hari ini
                    $startDate = $now->copy()->subMonths(6)->startOfDay();
                    $query->whereBetween('borrow_date', [$startDate, $now]);
                    break;

                case 'last_year':
                    // Tahun lalu: dari awal sampai akhir tahun lalu
                    $startDate = $now->copy()->subYear()->startOfYear();
                    $endDate = $now->copy()->subYear()->endOfYear();
                    $query->whereBetween('borrow_date', [$startDate, $endDate]);
                    break;
            }
        }

        $borrowings = $query->orderBy('borrow_date', 'desc')->paginate(10);

        // Get reading statistics for the user
        $userId = Auth::id();
        $totalBorrowed = Borrowing::where('member_id', $userId)->count();
        $currentlyBorrowed = Borrowing::where('member_id', $userId)
                                    ->where('status', 'borrowed')
                                    ->count();
        $returnedLate = Borrowing::where('member_id', $userId)
                                ->where('status', 'returned')
                                ->whereRaw('DATEDIFF(return_date, borrow_date) > 14')
                                ->count();

        // Calculate average borrowing time
        $averageBorrowDays = Borrowing::where('member_id', $userId)
                                    ->where('status', 'returned')
                                    ->whereNotNull('return_date')
                                    ->select(DB::raw('AVG(DATEDIFF(return_date, borrow_date)) as avg_days'))
                                    ->first()
                                    ->avg_days ?? 0;

        return view('borrowings.history', compact(
            'borrowings',
            'totalBorrowed',
            'currentlyBorrowed',
            'returnedLate',
            'averageBorrowDays'
        ));
    }
}
