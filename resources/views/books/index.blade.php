@extends('layouts.app')

@section('title', 'Buku - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Koleksi Buku</h1>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('books.create') }}" class="btn btn-primary">Tambah Buku Baru</a>
            @endif
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Cari Buku</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('books.index') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="title" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ request('title') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="author" class="form-label">Penulis</label>
                        <input type="text" class="form-control" id="author" name="author"
                            value="{{ request('author') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn"
                            value="{{ request('isbn') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="{{ route('books.index') }}" class="btn btn-secondary">Atur Ulang</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>ISBN</th>
                                <th>Tahun Terbit</th>
                                <th>Tersedia</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($books as $book)
                                <tr>
                                    <td>{{ $book->title }}</td>
                                    <td>{{ $book->author ?? 'Tidak Diketahui' }}</td>
                                    <td>{{ $book->isbn }}</td>
                                    <td>{{ $book->year_published ?? 'Tidak Diketahui' }}</td>
                                    <td>
                                        @if ($book->quantity_available > 0)
                                            <span class="badge bg-success">{{ $book->quantity_available }} Tersedia</span>
                                        @else
                                            <span class="badge bg-danger">Tidak Tersedia</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('books.show', $book) }}"
                                                class="btn btn-sm btn-info">Detail</a>

                                            @if (Auth::user()->isAdmin())
                                                <a href="{{ route('books.edit', $book) }}"
                                                    class="btn btn-sm btn-warning">Edit</a>
                                                <form action="{{ route('books.destroy', $book) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            @endif

                                            @if ($book->quantity_available > 0)
                                                <a href="{{ route('borrowings.create', ['book_id' => $book->book_id]) }}"
                                                    class="btn btn-sm btn-success">Pinjam</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada buku yang ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $books->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
