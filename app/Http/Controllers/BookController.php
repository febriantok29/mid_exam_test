<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\ValidatorException; // Assuming you have a custom exception for validation errors

class BookController extends Controller
{
    protected BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * Display a listing of books
     */
    public function index(Request $request): View
    {
        try {
            $filters = $this->getFiltersFromRequest($request);
            $books = $this->bookService->getBooks($filters);

            return view('books.index', [
                'books' => $books,
                'filters' => $this->getActiveFilters($request),
            ]);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new book
     */
    public function create(): View
    {
        return view('books.create');
    }

    /**
     * Store a newly created book
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'isbn' => 'required|string|max:20',
                'title' => 'required|string|max:150',
                'author' => 'nullable|string|max:100',
                'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                'quantity_available' => 'required|integer|min:0',
            ]);

            // Add user_id to the data
            $validatedData['user_id'] = Auth::id();

            $this->bookService->createBook($validatedData);
            return redirect()->route('books.index')->with('success', 'Buku berhasil ditambahkan.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified book
     */
    public function show(int $id): View
    {
        try {
            $book = $this->bookService->getBook($id);
            $borrowedByUser = Auth::check() ? $this->bookService->isBookBorrowedByUser($id, Auth::id()) : false;

            return view('books.show', compact('book', 'borrowedByUser'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified book
     */
    public function edit(int $id): View
    {
        try {
            $book = $this->bookService->getBook($id);
            return view('books.edit', compact('book'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified book
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $validatedData = $request->validate([
                'isbn' => 'required|string|max:20',
                'title' => 'required|string|max:150',
                'author' => 'nullable|string|max:100',
                'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                'quantity_available' => 'required|integer|min:0',
            ]);


            $validatedData['user_id'] = Auth::id();

            $this->bookService->updateBook($id, $validatedData);
            return redirect()->route('books.show', $id)->with('success', 'Buku berhasil diperbarui.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified book
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->bookService->deleteBook($id, [
                'user_id' => Auth::id()
            ]);
            return redirect()->route('books.index')->with('success', 'Buku berhasil dihapus.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get filters from request
     */
    private function getFiltersFromRequest(Request $request): array
    {
        $filters = [
            'title' => $request->get('title'),
            'author' => $request->get('author'),
            'isbn' => $request->get('isbn'),
            'page' => $request->get('page', 1),
            'per_page' => 10,
            'sort_by' => $request->get('sort_by', 'title'),
            'sort_order' => $request->get('sort_order', 'asc'),
        ];

        return array_filter($filters, fn($value) => $value !== null && $value !== '');
    }

    /**
     * Get active filters for the view
     */
    private function getActiveFilters(Request $request): array
    {
        return [
            'title' => $request->get('title'),
            'author' => $request->get('author'),
            'isbn' => $request->get('isbn'),
        ];
    }
}
