<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Borrowing;
use App\Models\User;
use Exception;
use App\Exceptions\ValidatorException;
use App\Models\Book;
// import DB
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $userId = request()->input('user_id');

            if (!$userId) {
                throw new ValidatorException('Silakan login terlebih dahulu.');
            }

            $query = Borrowing::where('member_id', $userId);

            if (request()->has('search')) {
                $search = request()->input('search');
                $query->where(function ($q) use ($search) {
                    $q->whereHas('book', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%')->orWhere('author', 'like', '%' . $search . '%');
                    });
                });
            }

            if (request()->has('status')) {
                $status = request()->input('status');

                if ($status == 'overdue') {
                    $query->where(function ($q) {
                        $q->where(function ($q2) {
                            // Peminjaman yang masih dipinjam dan terlambat
                            $q2->where('status', 'borrowed')->whereDate('borrow_date', '<', now()->subDays(14));
                        })->orWhere(function ($q2) {
                            // Peminjaman yang sudah dikembalikan tapi terlambat
                            $q2->where('status', 'returned')->whereRaw('DATEDIFF(return_date, borrow_date) > 14');
                        });
                    });
                } else {
                    $query->where('status', $status);
                }
            }

            if (request()->has('sort_by')) {
                $sortBy = request()->input('sort_by');
                $sortOrder = request()->input('sort_order', 'asc');
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('borrow_date', 'desc');
            }

            $borrowings = $query->with(['book'])->get();

            return response()->json(
                [
                    'message' => 'Daftar peminjaman berhasil diambil.',
                    'data' => $borrowings,
                ],
                200,
            );
        } catch (ValidatorException $e) {
            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';

            return response()->json(
                [
                    'error' => $errorMessage,
                ],
                422,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
    public function store(Request $request)
    {
        // Start the transaction
        DB::beginTransaction();

        try {
            $userId = $request->input('user_id');

            if (!$userId) {
                throw new ValidatorException('Silakan login terlebih dahulu.');
            }

            $bookId = $request->input('book_id');
            if (!$bookId) {
                throw new ValidatorException('ID buku tidak boleh kosong.');
            }

            // Find the book by the provided ID
            $book = Book::find($bookId);

            // If book not found by direct ID, try to find it by a different ID field
            // This handles cases where front-end sends a different ID format
            if (!$book) {
                $book = Book::where('book_id', $bookId)->first();

                if (!$book) {
                    throw new ValidatorException("Buku dengan ID {$bookId} tidak ditemukan.");
                }
            }

            // Ensure the book is available
            if ($book->quantity_available <= 0) {
                throw new ValidatorException("Buku dengan ID {$bookId} tidak tersedia untuk dipinjam.");
            }

            $borrowing = Borrowing::create([
                'member_id' => $userId,
                'book_id' => $book->book_id,
                'borrow_date' => now(),
                'status' => 'borrowed',
            ]);

            // Decrease the book's available quantity
            $book->quantity_available--;
            $book->save();

            // If everything is fine, commit the transaction
            DB::commit();

            return response()->json(
                [
                    'message' => 'Peminjaman berhasil dibuat.',
                    'data' => $borrowing,
                ],
                201,
            );
        } catch (ValidatorException $e) {
            // Rollback the transaction
            DB::rollBack();

            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';
            return response()->json(['error' => $errorMessage], 422);
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            return response()->json(
                [
                    'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $borrowing = Borrowing::with(['book'])->findOrFail($id);

            return response()->json(
                [
                    'message' => 'Detail peminjaman berhasil diambil.',
                    'data' => $borrowing,
                ],
                200,
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'error' => $e->getMessage(),
                ],
                404,
            );
        }
    }
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $userId = $request->input('user_id');

            if (!$userId) {
                throw new ValidatorException('Silakan login terlebih dahulu.');
            }

            $borrowing = Borrowing::findOrFail($id);

            $user = User::find($userId);
            if (!$user) {
                throw new ValidatorException('Pengguna tidak ditemukan.');
            }

            if ($borrowing->member_id !== $userId && $user->role !== 'admin') {
                throw new ValidatorException('Peminjaman tidak ditemukan.');
            }

            if ($borrowing->status === 'returned') {
                throw new ValidatorException('Peminjaman sudah dikembalikan.');
            }

            $borrowing->status = 'returned';
            $borrowing->return_date = $request->input('return_date', now());
            $borrowing->save();

            $book = $borrowing->book;
            if ($book) {
                $book->quantity_available++;
                $book->save();
            } else {
                throw new ValidatorException('Buku terkait tidak ditemukan.');
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Peminjaman berhasil diperbarui.',
                    'data' => $borrowing,
                ],
                200,
            );
        } catch (ValidatorException $e) {
            DB::rollBack();

            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';
            return response()->json(['error' => $errorMessage], 422);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $userId = request()->input('user_id');

            if (!$userId) {
                throw new ValidatorException('Silakan login terlebih dahulu.');
            }

            $borrowing = Borrowing::findOrFail($id);

            if ($borrowing->member_id !== $userId) {
                throw new ValidatorException('Peminjaman tidak ditemukan.');
            }

            $borrowing->delete();

            DB::commit();

            return response()->json(
                [
                    'message' => 'Peminjaman berhasil dihapus.',
                ],
                200,
            );
        } catch (ValidatorException $e) {
            DB::rollBack();

            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';
            return response()->json(['error' => $errorMessage], 422);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Export borrowing data for admin
     */    public function exportData()
    {
        try {
            // Set Carbon locale to Indonesian
            \Carbon\Carbon::setLocale('id');
            // Set PHP locale for formatLocalized method
            setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

            $userId = request()->input('user_id');
            $user = User::find($userId);

            if (!$userId || !$user || $user->role !== 'admin') {
                throw new ValidatorException('Anda tidak memiliki akses untuk ekspor data.');
            }

            $query = Borrowing::query();

            // Apply filters if provided
            if (request()->has('search')) {
                $search = request()->input('search');
                $query->where(function ($q) use ($search) {
                    $q->whereHas('book', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%');
                    })->orWhereHas('member', function ($q) use ($search) {
                        $q->where('full_name', 'like', '%' . $search . '%');
                    });
                });
            }

            if (request()->has('status')) {
                $status = request()->input('status');

                if ($status == 'overdue') {
                    $query->where(function ($q) {
                        $q->where(function ($q2) {
                            // Peminjaman yang masih dipinjam dan terlambat
                            $q2->where('status', 'borrowed')->whereDate('borrow_date', '<', now()->subDays(14));
                        })->orWhere(function ($q2) {
                            // Peminjaman yang sudah dikembalikan tapi terlambat
                            $q2->where('status', 'returned')->whereRaw('DATEDIFF(return_date, borrow_date) > 14');
                        });
                    });
                } else {
                    $query->where('status', $status);
                }
            }

            if (request()->has('date_range')) {
                $dateRange = request()->input('date_range');
                switch ($dateRange) {
                    case 'last_month':
                        $query->whereDate('borrow_date', '>=', now()->subMonth());
                        break;
                    case 'last_3_months':
                        $query->whereDate('borrow_date', '>=', now()->subMonths(3));
                        break;
                    case 'last_6_months':
                        $query->whereDate('borrow_date', '>=', now()->subMonths(6));
                        break;
                    case 'last_year':
                        $query->whereDate('borrow_date', '>=', now()->subYear());
                        break;
                }
            }

            // Include relations for complete data
            $borrowings = $query->with(['book', 'member'])->get();

            // Transform data for export
            $borrowingsData = $borrowings->map(function ($borrowing) {
                $borrowDate = is_string($borrowing->borrow_date)
                    ? \Carbon\Carbon::parse($borrowing->borrow_date)
                    : $borrowing->borrow_date;

                $dueDate = $borrowDate->copy()->addDays(14);

                $returnDate = null;
                $lateDays = 0;
                $latenessText = '';
                $status = $borrowing->status;
                $statusLabel = ($status === 'borrowed') ? 'Masih Dipinjam' : 'Dikembalikan';
                $today = now();

                if ($borrowing->return_date) {
                    $returnDate = is_string($borrowing->return_date)
                        ? \Carbon\Carbon::parse($borrowing->return_date)
                        : $borrowing->return_date;

                    if ($returnDate->gt($dueDate)) {
                        // Returned late
                        $daysLate = abs(intval($dueDate->diffInDays($returnDate)));
                        $lateDays = $daysLate;
                        $latenessText = "Telat {$daysLate} hari";
                    } else {
                        // Returned on time
                        $lateDays = 0;
                        $latenessText = 'Tepat waktu';
                    }
                } elseif ($borrowing->status === 'borrowed' && $today->gt($dueDate)) {
                    // Currently overdue
                    $daysLate = abs(intval($dueDate->diffInDays($today)));
                    $lateDays = $daysLate;
                    $latenessText = "{$daysLate} hari terlambat";
                    $statusLabel = 'Terlambat';
                } else {
                    // Not yet overdue - calculate days remaining
                    $daysLeft = abs(intval($dueDate->diffInDays($today)));
                    $lateDays = 0;
                    $latenessText = "{$daysLeft} hari tersisa";
                }

                return [
                    'id' => $borrowing->id,
                    'member_name' => $borrowing->member->full_name ?? 'Tidak diketahui',
                    'book_title' => $borrowing->book->title ?? 'Tidak diketahui',
                    'book_author' => $borrowing->book->author ?? 'Tidak diketahui',
                    'borrow_date' => $this->formatDateWithoutComma($borrowDate),
                    'due_date' => $this->formatDateWithoutComma($dueDate),
                    'return_date' => $returnDate ? $this->formatDateWithoutComma($returnDate) : null,
                    'status' => $statusLabel,
                    'late_days' => abs($lateDays),
                    'lateness_text' => $latenessText,
                    'fine_amount' => $lateDays > 0 ? (abs($lateDays) * 1000) : 0, // Rp 1.000/hari
                ];
            });

            return response()->json([
                'message' => 'Data peminjaman berhasil diekspor.',
                'data' => $borrowingsData,
            ], 200);
        } catch (ValidatorException $e) {
            $errorMessage = $e->errors() ? array_values($e->errors())[0][0] : 'Terjadi kesalahan validasi.';

            return response()->json([
                'error' => $errorMessage,
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format date without commas in day name
     *
     * @param \Carbon\Carbon|string $date
     * @return string|null
     */
    private function formatDateWithoutComma($date)
    {
        if (!$date) {
            return null;
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        $monthNames = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];

        $dayOfWeek = $dayNames[$date->format('l')] ?? $date->format('l');
        $day = $date->format('j');
        $month = $monthNames[$date->format('F')] ?? $date->format('F');
        $year = $date->format('Y');

        return "$dayOfWeek $day $month $year";
    }
}
