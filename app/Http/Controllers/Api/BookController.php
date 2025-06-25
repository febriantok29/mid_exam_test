<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\User;
use Exception;
use App\Exceptions\ValidatorException;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $query = Book::query();

            if (request()->has('search')) {
                $search = request()->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('isbn', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('author', 'like', '%' . $search . '%');
                });
            }

            if (request()->has('sort_by')) {
                $sortBy = request()->input('sort_by');
                $sortOrder = request()->input('sort_order', 'asc');
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('title', 'asc');
            }

            $books = $query->get();

            return response()->json(
                [
                    'message' => 'Daftar buku berhasil diambil.',
                    'data' => $books,
                ],
                200,
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'isbn' => 'required|string|max:13',
                    'title' => 'required|string|max:255',
                    'author' => 'required|string|max:255',
                    'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                    'quantity_available' => 'nullable|integer|min:0',
                ],
                [
                    'isbn.required' => 'ISBN harus diisi.',
                    'title.required' => 'Judul buku harus diisi.',
                    'author.required' => 'Penulis buku harus diisi.',
                    'year_published.integer' => 'Tahun terbit harus berupa angka.',
                    'quantity_available.integer' => 'Jumlah yang tersedia harus berupa angka.',
                    'quantity_available.min' => 'Jumlah yang tersedia tidak boleh kurang dari 0.',
                    'isbn.max' => 'ISBN tidak boleh lebih dari 13 karakter.',
                    'title.max' => 'Judul buku tidak boleh lebih dari 255 karakter.',
                    'author.max' => 'Penulis buku tidak boleh lebih dari 255 karakter.',
                    'year_published.min' => 'Tahun terbit tidak boleh kurang dari 1800.',
                    'year_published.max' => 'Tahun terbit tidak boleh lebih dari tahun ini.',
                ],
            );

            $validatedData['quantity_available'] = $validatedData['quantity_available'] ?? 0;

            $book = Book::create($validatedData);

            return response()->json(
                [
                    'message' => 'Buku berhasil ditambahkan.',
                    'data' => $book,
                ],
                201,
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $book = Book::findOrFail($id);

            return response()->json(
                [
                    'message' => 'Detail buku berhasil diambil.',
                    'data' => $book,
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $book = Book::findOrFail($id);

            $validatedData = $request->validate([
                'isbn' => 'required|string|max:13',
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'year_published' => 'nullable|integer|min:1800|max:' . date('Y'),
                'quantity_available' => 'required|integer|min:0',
            ]);

            $book->update($validatedData);

            return response()->json(
                [
                    'message' => 'Buku berhasil diperbarui.',
                    'data' => $book,
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();

            return response()->json(
                [
                    'message' => 'Buku berhasil dihapus.',
                ],
                200,
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
}
