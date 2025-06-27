<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $query = Book::query();

            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            $books = $query->paginate($perPage);

            return response()->json([
                'message' => 'Daftar buku berhasil diambil.',
                'data' => $books->items(),
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'from' => $books->firstItem(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'to' => $books->lastItem(),
                    'total' => $books->total(),
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Store a newly created book.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'isbn' => 'required|string|max:13|unique:books',
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                'quantity_available' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validasi gagal',
                    'messages' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $book = Book::create($validator->validated());

            return response()->json([
                'message' => 'Buku berhasil ditambahkan.',
                'data' => $book,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified book.
     */
    public function show(int $id)
    {
        try {
            $book = Book::findOrFail($id);

            return response()->json([
                'message' => 'Detail buku berhasil diambil.',
                'data' => $book,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified book.
     */
    public function update(Request $request, int $id)
    {
        try {
            $book = Book::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'isbn' => 'required|string|max:13|unique:books,isbn,' . $id . ',book_id',
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                'quantity_available' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validasi gagal',
                    'messages' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $book->update($validator->validated());

            return response()->json([
                'message' => 'Buku berhasil diperbarui.',
                'data' => $book,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified book.
     */
    public function destroy(int $id)
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();

            return response()->json([
                'message' => 'Buku berhasil dihapus.',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Check if a book is borrowed by a specific user.
     */
    public function checkBorrowStatus(int $bookId, int $userId)
    {
        try {
            $isBorrowed = Book::findOrFail($bookId)
                ->borrowings()
                ->where('member_id', $userId)
                ->whereNull('return_date')
                ->exists();

            return response()->json([
                'data' => [
                    'is_borrowed' => $isBorrowed,
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $filters = [
            'title' => fn($value) => $query->where('title', 'like', "%{$value}%"),
            'author' => fn($value) => $query->where('author', 'like', "%{$value}%"),
            'isbn' => fn($value) => $query->where('isbn', 'like', "%{$value}%"),
            'year_published' => fn($value) => $query->where('year_published', $value),
        ];

        foreach ($filters as $param => $callback) {
            if ($request->has($param) && $request->filled($param)) {
                $callback($request->input($param));
            }
        }
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->input('sort_by', 'title');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSortFields = ['title', 'author', 'isbn', 'year_published', 'quantity_available'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
    }

    /**
     * Return error response.
     */
    private function errorResponse(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        return response()->json([
            'error' => $message,
        ], $status);
    }
}
