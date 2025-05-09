<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /**
     * Constructor to apply middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->except(['index', 'show']);
    }
    
    /**
     * Display a listing of books
     */
    public function index(Request $request)
    {
        $query = Book::query();
        
        // Search by title
        if ($request->has('title') && $request->title) {
            $query->where('title', 'like', "%{$request->title}%");
        }
        
        // Search by author
        if ($request->has('author') && $request->author) {
            $query->where('author', 'like', "%{$request->author}%");
        }
        
        // Search by isbn
        if ($request->has('isbn') && $request->isbn) {
            $query->where('isbn', 'like', "%{$request->isbn}%");
        }
        
        $books = $query->paginate(10);
        
        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new book
     */
    public function create()
    {
        return view('books.create');
    }

    /**
     * Store a newly created book in database
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'isbn' => 'required|string|max:20|unique:books',
            'title' => 'required|string|max:150',
            'author' => 'nullable|string|max:100',
            'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
            'quantity_available' => 'required|integer|min:0',
        ]);

        Book::create($validatedData);
        
        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    /**
     * Display the specified book
     */
    public function show(Book $book)
    {
        // Check if the current user has borrowed this book
        $borrowedByUser = null;
        if (Auth::check()) {
            $borrowedByUser = $book->borrowings()
                ->where('member_id', Auth::id())
                ->where('status', 'borrowed')
                ->first();
        }
        
        return view('books.show', compact('book', 'borrowedByUser'));
    }

    /**
     * Show the form for editing the specified book
     */
    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    /**
     * Update the specified book in database
     */
    public function update(Request $request, Book $book)
    {
        $validatedData = $request->validate([
            'isbn' => 'required|string|max:20|unique:books,isbn,' . $book->book_id . ',book_id',
            'title' => 'required|string|max:150',
            'author' => 'nullable|string|max:100',
            'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
            'quantity_available' => 'required|integer|min:0',
        ]);

        $book->update($validatedData);
        
        return redirect()->route('books.show', $book)
            ->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Remove the specified book from database
     */
    public function destroy(Book $book)
    {
        // Check if book has active borrowings
        if ($book->borrowings()->where('status', 'borrowed')->exists()) {
            return redirect()->route('books.index')
                ->with('error', 'Tidak dapat menghapus buku yang sedang dipinjam.');
        }
        
        $book->delete();
        
        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil dihapus.');
    }
}
