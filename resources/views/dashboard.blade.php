@extends('layouts.app')

@section('title', 'Dasbor - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <h1 class="mb-4">Dasbor</h1>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Telusuri Buku</h5>
                        <p class="card-text">Jelajahi koleksi buku kami dan pinjam yang Anda minati.</p>
                        <a href="{{ route('books.index') }}" class="btn btn-primary">Lihat Buku</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Peminjaman Saya</h5>
                        <p class="card-text">Kelola peminjaman buku Anda saat ini dan kembalikan buku.</p>
                        <a href="{{ route('borrowings.index') }}" class="btn btn-primary">Lihat Peminjaman Saya</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Riwayat Peminjaman</h5>
                        <p class="card-text">Lihat riwayat peminjaman lengkap Anda untuk melacak kebiasaan membaca.</p>
                        <a href="{{ route('borrowings.history') }}" class="btn btn-primary">Lihat Riwayat</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Buku Terbaru</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Tersedia</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBooks as $book)
                                <tr>
                                    <td>{{ $book->title }}</td>
                                    <td>{{ $book->author }}</td>
                                    <td>
                                        @if ($book->quantity_available > 0)
                                            <span class="badge bg-success">{{ $book->quantity_available }} Tersedia</span>
                                        @else
                                            <span class="badge bg-danger">Tidak Tersedia</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-info">Detail</a>
                                        @if ($book->quantity_available > 0)
                                            <a href="{{ route('borrowings.create', ['book_id' => $book->book_id]) }}"
                                                class="btn btn-sm btn-success">Pinjam</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada buku terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $recentBooks->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
