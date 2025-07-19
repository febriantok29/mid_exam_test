@extends('layouts.app')

@section('title', 'Pinjam Buku - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Buku</a></li>
                <li class="breadcrumb-item"><a href="{{ route('books.show', $book['book_id']) }}">{{ $book['title'] }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pinjam</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Detail Peminjaman</h5>
                    </div>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <h4>{{ $book['title'] }}</h4>
                            <p class="text-muted">{{ $book['author'] ?? 'Tidak Diketahui' }}</p>
                            @if ($book['quantity_available'] > 0)
                                <span class="badge bg-success">{{ $book['quantity_available'] }} Tersedia</span>
                            @else
                                <span class="badge bg-danger">Tidak Tersedia</span>
                            @endif
                        </div>

                        <form action="{{ route('borrowings.store') }}" method="POST">
                            @csrf

                            <input type="hidden" name="book_id" value="{{ $book['book_id'] }}">

                            <div class="mb-3">
                                <label for="borrow_date" class="form-label">Tanggal Pinjam</label>
                                <input type="date" class="form-control @error('borrow_date') is-invalid @enderror"
                                    id="borrow_date" name="borrow_date" value="{{ old('borrow_date', date('Y-m-d')) }}"
                                    required>
                                @error('borrow_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms"
                                        required>
                                    <label class="form-check-label" for="accept_terms">
                                        Saya setuju untuk mengembalikan buku tepat waktu dan dalam kondisi baik
                                    </label>
                                    @error('accept_terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary">Pinjam Buku</button>
                                <a href="{{ route('books.show', $book['book_id']) }}" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
