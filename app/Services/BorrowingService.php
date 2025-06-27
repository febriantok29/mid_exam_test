<?php

namespace App\Services;

use App\Http\Utilities\ApiClient;
use App\Http\Utilities\ApiMethod;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exceptions\ValidatorException;

class BorrowingService
{
    protected ApiClient $apiClient;

    public function __construct(ApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new ApiClient();
    }

    /**
     * Get borrowings for the current user or with filters
     */
    public function getBorrowings(array $filters = []): LengthAwarePaginator
    {
        try {
            // Add user_id to filters
            $filters['user_id'] = Auth::id();

            // Clean up empty filters
            $validFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            // Use ApiClient to make the request
            $response = $this->apiClient
                ->withQueries($validFilters)
                ->call(ApiMethod::GET, 'borrowings');

            $borrowings = $response['data'] ?? [];
            $meta = $response['meta'] ?? [
                'current_page' => $filters['page'] ?? 1,
                'per_page' => $filters['per_page'] ?? 10,
                'total' => count($borrowings)
            ];

            // Create a paginator with the response data
            return new LengthAwarePaginator(
                $this->normalizeBorrowings($borrowings),
                $meta['total'],
                $meta['per_page'],
                $meta['current_page'],
                [
                    'path' => request()->url(),
                    'query' => $validFilters
                ]
            );
        } catch (Exception $e) {
            throw new Exception('Gagal mengambil data peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific borrowing by ID
     */    public function getBorrowing(int $id): array
    {
        try {
            // Add user_id to query
            $response = $this->apiClient
                ->withQueries(['user_id' => Auth::id()])
                ->call(ApiMethod::GET, "borrowings/{$id}");

            // Check if the data key exists in the response
            if (!isset($response['data'])) {
                if (isset($response['error'])) {
                    throw new Exception($response['error']);
                }
                throw new Exception('Peminjaman tidak ditemukan atau data tidak tersedia');
            }

            return $this->normalizeBorrowing($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal mengambil detail peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Create a new borrowing
     */
    public function createBorrowing(array $data): array
    {
        try {
            // Add user_id to data
            $data['user_id'] = Auth::id();

            // Ensure book_id is properly included
            if (empty($data['book_id'])) {
                throw new Exception('ID buku tidak boleh kosong.');
            }

            // Ensure borrow_date is included
            if (empty($data['borrow_date'])) {
                $data['borrow_date'] = now()->format('Y-m-d');
            }

            $response = $this->apiClient
                ->call(ApiMethod::POST, 'borrowings', $data);

            // If response is empty or doesn't contain data, create a default structure
            if (empty($response) || !isset($response['data'])) {
                // Create a minimal borrowing record that can be normalized
                $defaultBorrowing = [
                    'borrowing_id' => null,
                    'member_id' => Auth::id(),
                    'book_id' => $data['book_id'] ?? null,
                    'borrow_date' => $data['borrow_date'] ?? now()->format('Y-m-d'),
                    'status' => 'borrowed',
                ];

                // If there's a success message but no data, this might be intentional
                if (isset($response['message']) && !isset($response['error'])) {
                    return $this->normalizeBorrowing($defaultBorrowing);
                }

                // If there's an error message, throw with that message
                if (isset($response['error'])) {
                    throw new Exception($response['error']);
                }

                // Fallback with a more descriptive error
                throw new Exception('API tidak mengembalikan data dalam format yang diharapkan');
            }

            return $this->normalizeBorrowing($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal membuat peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Return a borrowed book
     */    public function returnBorrowing(int $id, array $data = []): array
    {
        try {
            // Add user_id to data
            $data['user_id'] = Auth::id();

            $response = $this->apiClient
                ->call(ApiMethod::POST, "borrowings/return/{$id}", $data);

            // Check if the data key exists in the response
            if (!isset($response['data'])) {
                if (isset($response['error'])) {
                    throw new Exception($response['error']);
                }

                // Create default return data based on what we know
                $defaultReturn = [
                    'borrowing_id' => $id,
                    'member_id' => Auth::id(),
                    'return_date' => $data['return_date'] ?? now()->format('Y-m-d'),
                    'status' => 'returned',
                ];

                // If there's a success message but no data, this might be intentional
                if (isset($response['message']) && !isset($response['error'])) {
                    return $this->normalizeBorrowing($defaultReturn);
                }

                throw new Exception('Gagal mengembalikan buku: Format data tidak sesuai');
            }

            return $this->normalizeBorrowing($response['data']);
        } catch (Exception $e) {
            throw new Exception('Gagal mengembalikan peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Delete a borrowing record
     */
    public function deleteBorrowing(int $id): bool
    {
        try {
            $this->apiClient
                ->withQueries(['user_id' => Auth::id()])
                ->call(ApiMethod::DELETE, "borrowings/{$id}");

            return true;
        } catch (Exception $e) {
            throw new Exception('Gagal menghapus peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Normalize a collection of borrowings
     */
    private function normalizeBorrowings(array $borrowings): array
    {
        return array_map([$this, 'normalizeBorrowing'], $borrowings);
    }

    /**
     * Normalize a single borrowing record
     */
    private function normalizeBorrowing(array $borrowing): array
    {
        $requiredFields = [
            'borrowing_id' => null,
            'member_id' => null,
            'book_id' => null,
            'borrow_date' => null,
            'return_date' => null,
            'status' => null,
            'book' => [],
            'display_status' => [
                'text' => '',
                'class' => '',
                'tooltip' => ''
            ],
            'late_status' => [
                'text' => '',
                'class' => ''
            ],
            'formatted_borrow_date' => '',
            'formatted_return_date' => null,
        ];

        // Merge with defaults
        $normalized = array_merge($requiredFields, $borrowing);

        // Handle special cases and conversions
        if (!empty($normalized['borrow_date'])) {
            $borrowDate = Carbon::parse($normalized['borrow_date']);
            $normalized['formatted_borrow_date'] = $borrowDate->translatedFormat('l, j F Y');

            // Calculate display status if not provided by API
            if (empty($normalized['display_status']['text'])) {
                $normalized['display_status'] = $this->calculateDisplayStatus($normalized);
            }

            // Calculate late status if not provided by API
            if (empty($normalized['late_status']['text'])) {
                $normalized['late_status'] = $this->calculateLateStatus($normalized);
            }
        }

        if (!empty($normalized['return_date'])) {
            $returnDate = Carbon::parse($normalized['return_date']);
            $normalized['formatted_return_date'] = $returnDate->translatedFormat('l, j F Y');
        }

        return $normalized;
    }

    /**
     * Calculate display status for a borrowing
     */
    private function calculateDisplayStatus(array $borrowing): array
    {
        $borrowDate = Carbon::parse($borrowing['borrow_date']);
        $now = now();

        // Validate data
        if ($borrowDate->isFuture()) {
            return [
                'text' => 'Data Invalid',
                'class' => 'bg-danger',
                'tooltip' => 'Tanggal peminjaman tidak boleh di masa depan'
            ];
        }

        // For returned books
        if ($borrowing['status'] === 'returned' && !empty($borrowing['return_date'])) {
            $returnDate = Carbon::parse($borrowing['return_date']);
            if ($borrowDate->diffInDays($returnDate) > 14) {
                $days = $borrowDate->diffInDays($returnDate);
                return [
                    'text' => 'Dikembalikan Terlambat',
                    'class' => 'bg-warning',
                    'tooltip' => "Dikembalikan setelah {$days} hari"
                ];
            }
            return [
                'text' => 'Dikembalikan',
                'class' => 'bg-success',
                'tooltip' => 'Dikembalikan tepat waktu'
            ];
        }

        // For books still borrowed
        $daysFromBorrow = $borrowDate->diffInDays($now);

        if ($daysFromBorrow > 14) {
            return [
                'text' => 'Perlu Dikembalikan',
                'class' => 'bg-danger',
                'tooltip' => 'Terlambat ' . ($daysFromBorrow - 14) . ' hari'
            ];
        }

        return [
            'text' => 'Sedang Dipinjam',
            'class' => 'bg-warning',
            'tooltip' => 'Sisa ' . max(0, 14 - $daysFromBorrow) . ' hari lagi'
        ];
    }

    /**
     * Calculate late status for a borrowing
     */
    private function calculateLateStatus(array $borrowing): array
    {
        $borrowDate = Carbon::parse($borrowing['borrow_date']);
        $now = now();

        // Validate data
        if ($borrowDate->isFuture()) {
            return [
                'text' => 'Data Invalid',
                'class' => 'bg-danger'
            ];
        }

        // For returned books
        if ($borrowing['status'] === 'returned' && !empty($borrowing['return_date'])) {
            $returnDate = Carbon::parse($borrowing['return_date']);
            $borrowDays = $borrowDate->diffInDays($returnDate);

            if ($borrowDays > 14) {
                return [
                    'text' => 'Terlambat ' . ($borrowDays - 14) . ' hari',
                    'class' => 'bg-warning'
                ];
            }

            return [
                'text' => 'Tepat Waktu',
                'class' => 'bg-success'
            ];
        }

        // For books still borrowed
        $borrowDays = $borrowDate->diffInDays($now);

        if ($borrowDays > 14) {
            return [
                'text' => 'Terlambat ' . ($borrowDays - 14) . ' hari',
                'class' => 'bg-danger'
            ];
        }

        return [
            'text' => 'Sisa ' . max(0, 14 - $borrowDays) . ' hari',
            'class' => 'bg-success'
        ];
    }
}
