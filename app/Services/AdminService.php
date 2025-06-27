<?php

namespace App\Services;

use App\Http\Utilities\ApiClient;
use App\Http\Utilities\ApiMethod;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminService
{
    protected ApiClient $apiClient;

    public function __construct(ApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new ApiClient();
    }

    /**
     * Get dashboard data
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        try {
            $userId = Auth::id();

            // Add debug logging
            Log::info('AdminService: Calling dashboard API', [
                'user_id' => $userId,
                'is_admin' => Auth::user()->isAdmin() ?? false,
                'role' => Auth::user()->role ?? 'unknown'
            ]);

            // Ensure we pass the user_id for admin authentication
            $response = $this->apiClient
                ->withQueries(['user_id' => $userId])
                ->call(ApiMethod::GET, 'admin/dashboard');

            // Log the response for debugging
            Log::info('AdminService: Dashboard API response', [
                'has_data' => isset($response['data']),
                'data_keys' => isset($response['data']) ? array_keys($response['data']) : [],
                'error' => $response['error'] ?? 'none'
            ]);

            if (!isset($response['data'])) {
                if (isset($response['error'])) {
                    Log::error('AdminService: API error', [
                        'error' => $response['error']
                    ]);
                    throw new Exception($response['error']);
                }
                throw new Exception('Data dashboard tidak tersedia');
            }

            return $response['data'];
        } catch (Exception $e) {
            Log::error('AdminService: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Gagal mengambil data dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get all members
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getMembers(array $filters = []): LengthAwarePaginator
    {
        try {
            // Add user_id to filters
            $filters['user_id'] = Auth::id();

            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Log the request
            Log::info('AdminService: Calling members API', [
                'filters' => $validFilters,
                'user_id' => Auth::id()
            ]);

            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'admin/members');

            // Log the response
            Log::info('AdminService: Members API response', [
                'has_data' => isset($response['data']),
                'has_meta' => isset($response['meta']),
                'error' => $response['error'] ?? 'none'
            ]);

            $members = $response['data'] ?? [];
            $meta = $response['meta'] ?? [
                'current_page' => $filters['page'] ?? 1,
                'per_page' => $filters['per_page'] ?? 15,
                'total' => count($members)
            ];

            return new LengthAwarePaginator(
                collect($members),
                $meta['total'],
                $meta['per_page'],
                $meta['current_page'],
                [
                    'path' => request()->url(),
                    'query' => $validFilters
                ]
            );
        } catch (Exception $e) {
            Log::error('AdminService: Exception in getMembers', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Gagal mengambil data anggota: ' . $e->getMessage());
        }
    }

    /**
     * Get all borrowings for admin
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getBorrowings(array $filters = []): LengthAwarePaginator
    {
        try {
            // Add user_id to filters
            $filters['user_id'] = Auth::id();

            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Log the request
            Log::info('AdminService: Calling borrowings API', [
                'filters' => $validFilters
            ]);

            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'admin/borrowings');

            // Log the response
            Log::info('AdminService: Borrowings API response', [
                'has_data' => isset($response['data']),
                'has_meta' => isset($response['meta']),
                'error' => $response['error'] ?? 'none'
            ]);

            $borrowings = $response['data'] ?? [];
            $meta = $response['meta'] ?? [
                'current_page' => $filters['page'] ?? 1,
                'per_page' => $filters['per_page'] ?? 15,
                'total' => count($borrowings)
            ];

            return new LengthAwarePaginator(
                collect($borrowings),
                $meta['total'],
                $meta['per_page'],
                $meta['current_page'],
                [
                    'path' => request()->url(),
                    'query' => $validFilters
                ]
            );
        } catch (Exception $e) {
            Log::error('AdminService: Exception in getBorrowings', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Gagal mengambil data peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Get all books for admin
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getBooks(array $filters = []): LengthAwarePaginator
    {
        try {
            // Add user_id to filters
            $filters['user_id'] = Auth::id();

            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Log the request
            Log::info('AdminService: Calling books API', [
                'filters' => $validFilters
            ]);

            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'admin/books');

            // Log the response
            Log::info('AdminService: Books API response', [
                'has_data' => isset($response['data']),
                'has_meta' => isset($response['meta']),
                'error' => $response['error'] ?? 'none'
            ]);

            $books = $response['data'] ?? [];
            $meta = $response['meta'] ?? [
                'current_page' => $filters['page'] ?? 1,
                'per_page' => $filters['per_page'] ?? 15,
                'total' => count($books)
            ];

            return new LengthAwarePaginator(
                collect($books),
                $meta['total'],
                $meta['per_page'],
                $meta['current_page'],
                [
                    'path' => request()->url(),
                    'query' => $validFilters
                ]
            );
        } catch (Exception $e) {
            Log::error('AdminService: Exception in getBooks', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Gagal mengambil data buku: ' . $e->getMessage());
        }
    }

    /**
     * Get popular books report
     *
     * @param array $filters
     * @return array
     */
    public function getPopularBooks(array $filters = []): array
    {
        try {
            // Add user_id to filters
            $filters['user_id'] = Auth::id();

            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Log the request
            Log::info('AdminService: Calling popular books API', [
                'filters' => $validFilters
            ]);

            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'admin/popular-books');

            // Log the response
            Log::info('AdminService: Popular books API response', [
                'has_data' => isset($response['data']),
                'error' => $response['error'] ?? 'none'
            ]);

            if (!isset($response['data'])) {
                if (isset($response['error'])) {
                    Log::error('AdminService: API error in getPopularBooks', [
                        'error' => $response['error']
                    ]);
                    throw new Exception($response['error']);
                }
                throw new Exception('Data buku populer tidak tersedia');
            }

            return $response['data'];
        } catch (Exception $e) {
            Log::error('AdminService: Exception in getPopularBooks', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Gagal mengambil data buku populer: ' . $e->getMessage());
        }
    }
}
