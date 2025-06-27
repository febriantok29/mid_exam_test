<?php

namespace App\Services;

use App\Http\Controllers\Api\BookController;
use App\Http\Utilities\ApiClient;
use App\Http\Utilities\ApiMethod;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class BookService
{
    protected ApiClient $apiClient;

    public function __construct(ApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new ApiClient();
    }

    public function getBooks(array $filters = []): LengthAwarePaginator
    {
        try {
            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Use ApiClient to make the request
            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'books');

            $books = $response['data'] ?? [];
            $meta = $response['meta'] ?? [
                'current_page' => $filters['page'] ?? 1,
                'per_page' => $filters['per_page'] ?? 10,
                'total' => count($books)
            ];

            // Create paginator with the response data
            return new LengthAwarePaginator(
                $this->normalizeBooks($books),
                $meta['total'],
                $meta['per_page'],
                $meta['current_page'],
                [
                    'path' => request()->url(),
                    'query' => $validFilters
                ]
            );
        } catch (Exception $e) {
            throw new Exception('Gagal mengambil data buku: ' . $e->getMessage());
        }
    }

    public function getBook(int $id): array
    {
        try {
            $response = $this->apiClient
                ->call(ApiMethod::GET, "books/{$id}");
            return $this->normalizeBook($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal mengambil detail buku: ' . $e->getMessage());
        }
    }

    public function createBook(array $data): array
    {
        try {
            $response = $this->apiClient
                ->call(ApiMethod::POST, 'books', $data);
            return $this->normalizeBook($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal menambahkan buku: ' . $e->getMessage());
        }
    }

    public function updateBook(int $id, array $data): array
    {
        try {
            $response = $this->apiClient
                ->call(ApiMethod::PUT, "books/{$id}", $data);
            return $this->normalizeBook($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal memperbarui buku: ' . $e->getMessage());
        }
    }

    public function deleteBook(int $id, array $data = []): bool
    {
        try {
            $this->apiClient->call(ApiMethod::DELETE, "books/{$id}", $data);
            return true;
        } catch (Exception $e) {
            throw new Exception('Gagal menghapus buku: ' . $e->getMessage());
        }
    }

    public function isBookBorrowedByUser(int $bookId, int $userId): bool
    {
        try {
            $response = $this->apiClient
                ->call(ApiMethod::GET, "books/{$bookId}/check-borrow-status/{$userId}");
            return $response['data']['is_borrowed'] ?? false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function normalizeBooks(array $books): array
    {
        return array_map([$this, 'normalizeBook'], $books);
    }

    private function normalizeBook(array $book): array
    {
        $requiredFields = [
            'book_id' => null,
            'user_id' => null,
            'title' => '',
            'author' => null,
            'isbn' => '',
            'year_published' => null,
            'quantity_available' => 0,
        ];

        return array_merge($requiredFields, array_intersect_key($book, $requiredFields));
    }
}
