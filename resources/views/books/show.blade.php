@extends('layouts.app')

@section('title', 'Detail Buku - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Daftar Buku</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $book['title'] }}</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title h3">Detail Buku</h1>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">ISBN</th>
                                <td>{{ $book['isbn'] }}</td>
                            </tr>
                            <tr>
                                <th>Judul</th>
                                <td>{{ $book['title'] }}</td>
                            </tr>
                            <tr>
                                <th>Penulis</th>
                                <td>{{ $book['author'] ?? 'Tidak Diketahui' }}</td>
                            </tr>
                            <tr>
                                <th>Tahun Terbit</th>
                                <td>{{ $book['year_published'] ?? 'Tidak Diketahui' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if ($book['quantity_available'] > 0)
                                        <span class="badge bg-success">{{ $book['quantity_available'] }} Tersedia</span>
                                    @else
                                        <span class="badge bg-danger">Tidak Tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="btn-group">
                        @if (Auth::user() && Auth::user()->isAdmin())
                            <a href="{{ route('books.edit', ['book' => $book['book_id']]) }}" class="btn btn-warning">Edit</a>
                            <form action="{{ route('books.destroy', ['book' => $book['book_id']]) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        @endif

                        @if (Auth::check() && $book['quantity_available'] > 0 && !$borrowedByUser)
                            <a href="{{ route('borrowings.create', ['book_id' => $book['book_id']]) }}"
                                class="btn btn-success">Pinjam Buku</a>
                        @elseif ($borrowedByUser)
                            <span class="btn btn-secondary disabled">Sedang Anda Pinjam</span>
                        @endif

                        <a href="{{ route('books.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
