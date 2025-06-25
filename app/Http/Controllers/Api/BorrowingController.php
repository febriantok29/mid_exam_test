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
            $book = Book::findOrFail($bookId);

            $borrowing = Borrowing::create([
                'member_id' => $userId,
                'book_id' => $book->id,
                'borrow_date' => now(),
                'status' => 'borrowed',
            ]);

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

            if ($borrowing->member_id !== $userId) {
                throw new ValidatorException('Peminjaman tidak ditemukan.');
            }

            if ($borrowing->status === 'returned') {
                throw new ValidatorException('Peminjaman sudah dikembalikan.');
            }

            $borrowing->status = 'returned';
            $borrowing->return_date = $request->input('return_date', now());
            $borrowing->save();

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
}
