@extends('layouts.app')

@section('title', $book->title . ' - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Buku</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $book->title }}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="h2 mb-4">{{ $book->title }}</h1>

                        <div class="row">
                            <div class="col-md-8">
                                <table class="table">
                                    <tr>
                                        <th style="width: 200px;">ISBN</th>
                                        <td>{{ $book->isbn }}</td>
                                    </tr>
                                    <tr>
                                        <th>Penulis</th>
                                        <td>{{ $book->author ?? 'Tidak Diketahui' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tahun Terbit</th>
                                        <td>{{ $book->year_published ?? 'Tidak Diketahui' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if ($book->quantity_available > 0)
                                                <span class="badge bg-success">{{ $book->quantity_available }}
                                                    Tersedia</span>
                                            @else
                                                <span class="badge bg-danger">Tidak Tersedia</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                @if (Auth::user()->isAdmin())
                                    <div class="mt-4">
                                        <a href="{{ route('books.edit', $book) }}" class="btn btn-warning">Edit Buku</a>
                                        <form action="{{ route('books.destroy', $book) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Hapus Buku</button>
                                        </form>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    @if ($book->quantity_available > 0 && !$borrowedByUser)
                                        <a href="{{ route('borrowings.create', ['book_id' => $book->book_id]) }}"
                                            class="btn btn-success">Pinjam Buku Ini</a>
                                    @endif
                                    <a href="{{ route('books.index') }}" class="btn btn-secondary">Kembali ke Daftar
                                        Buku</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
