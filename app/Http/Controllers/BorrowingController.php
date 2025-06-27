<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use App\Services\BorrowingService;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BorrowingController extends Controller
{
    protected BorrowingService $borrowingService;
    protected BookService $bookService;

    public function __construct(BorrowingService $borrowingService, BookService $bookService)
    {
        $this->borrowingService = $borrowingService;
        $this->bookService = $bookService;
    }

    /**
     * Display a listing of the borrowings
     */
    public function index(): View
    {
        try {
            $borrowings = $this->borrowingService->getBorrowings([
                'per_page' => 10,
                'page' => request()->get('page', 1)
            ]);

            return view('borrowings.index', compact('borrowings'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for borrowing a new book
     */
    public function create(Request $request): View
    {
        try {
            // Handle the case where book_id is passed in the URL
            if ($request->has('book_id')) {
                $book = $this->bookService->getBook($request->book_id);
                return view('borrowings.create', compact('book'));
            }

            // Otherwise show all available books
            $availableBooks = $this->bookService->getBooks([
                'quantity_available_gt' => 0
            ]);

            return view('borrowings.create', compact('availableBooks'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process a book borrowing
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'book_id' => 'required|numeric',
                'borrow_date' => 'required|date|before_or_equal:today',
                'accept_terms' => 'required|accepted',
            ], [
                'borrow_date.before_or_equal' => 'Tanggal peminjaman tidak boleh di masa depan',
                'accept_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan peminjaman'
            ]);

            // Check if book exists and is available
            $book = $this->bookService->getBook($validatedData['book_id']);

            if ($book['quantity_available'] <= 0) {
                return redirect()->back()
                    ->with('error', 'Buku ini tidak tersedia untuk dipinjam.');
            }

            // Ensure the book_id is valid
            if (empty($book['book_id'])) {
                return redirect()->back()
                    ->with('error', 'ID buku tidak valid.');
            }

            // Log the book_id to debug
            \Illuminate\Support\Facades\Log::info('Attempting to borrow book', [
                'book_id' => $book['book_id'],
                'book_data' => $book
            ]);

            // Create borrowing through API
            $borrowingData = [
                'book_id' => $book['book_id'],
                'borrow_date' => $validatedData['borrow_date'],
            ];

            $this->borrowingService->createBorrowing($borrowingData);

            return redirect()->route('borrowings.index')
                ->with('success', 'Buku berhasil dipinjam.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal meminjam buku: ' . $e->getMessage());
        }
    }

    /**
     * Display borrowing details
     */
    public function show(int $id): View
    {
        try {
            $borrowing = $this->borrowingService->getBorrowing($id);

            return view('borrowings.show', compact('borrowing'));
        } catch (Exception $e) {
            return back()->with('error', 'Gagal mengambil detail peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Return a borrowed book
     */
    public function returnBook(int $id): RedirectResponse
    {
        try {
            // Get the borrowing details first
            $borrowing = $this->borrowingService->getBorrowing($id);

            // Check if book is already returned
            if ($borrowing['status'] === 'returned') {
                return redirect()->back()
                    ->with('error', 'Buku ini sudah dikembalikan.');
            }

            // Return the book through API
            $this->borrowingService->returnBorrowing($id, [
                'return_date' => now()->format('Y-m-d')
            ]);

            return redirect()->back()
                ->with('success', 'Buku berhasil dikembalikan.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal mengembalikan buku: ' . $e->getMessage());
        }
    }

    /**
     * Show history of borrowings
     */
    public function history(Request $request): View
    {
        try {
            // Build filters from request
            $filters = [
                'page' => $request->get('page', 1),
                'per_page' => 10
            ];

            // Add search filters if available
            if ($request->has('search')) {
                $filters['search'] = $request->search;
            }

            // Add status filters
            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }

            // Add date filters
            if ($request->has('date_range')) {
                $filters['date_range'] = $request->date_range;
            }

            // Get borrowings with our filters
            $borrowings = $this->borrowingService->getBorrowings($filters);

            // Calculate statistics based on the returned borrowings data
            // In a real application, you might want to add API endpoints for these stats
            $allBorrowings = $borrowings->items();

            $totalBorrowed = count($allBorrowings);

            $currentlyBorrowed = count(array_filter($allBorrowings, function($item) {
                return $item['status'] === 'borrowed';
            }));

            $returnedLate = count(array_filter($allBorrowings, function($item) {
                if ($item['status'] !== 'returned' || empty($item['return_date'])) {
                    return false;
                }
                $borrowDate = Carbon::parse($item['borrow_date']);
                $returnDate = Carbon::parse($item['return_date']);
                return $borrowDate->diffInDays($returnDate) > 14;
            }));

            // Calculate average borrowing time for returned books
            $returnedBorrowings = array_filter($allBorrowings, function($item) {
                return $item['status'] === 'returned' && !empty($item['return_date']);
            });

            $totalDays = 0;
            $returnedCount = count($returnedBorrowings);

            foreach ($returnedBorrowings as $borrowing) {
                $borrowDate = Carbon::parse($borrowing['borrow_date']);
                $returnDate = Carbon::parse($borrowing['return_date']);
                $totalDays += $borrowDate->diffInDays($returnDate);
            }

            $averageBorrowDays = $returnedCount > 0 ? round($totalDays / $returnedCount) : 0;

            return view('borrowings.history', compact(
                'borrowings',
                'totalBorrowed',
                'currentlyBorrowed',
                'returnedLate',
                'averageBorrowDays'
            ));
        } catch (Exception $e) {
            return back()->with('error', 'Gagal mengambil riwayat peminjaman: ' . $e->getMessage());
        }
    }
}
