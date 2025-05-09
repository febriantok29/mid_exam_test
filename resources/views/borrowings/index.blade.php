@extends('layouts.app')

@section('title', 'Peminjaman Saya - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <h1 class="mb-4">Peminjaman Aktif Saya</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                @if ($borrowings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Judul Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Status</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($borrowings as $borrowing)
                                    <tr>
                                        <td>
                                            <a
                                                href="{{ route('books.show', $borrowing->book) }}">{{ $borrowing->book->title }}</a>
                                        </td>
                                        <td>{{ $borrowing->borrow_date }}</td>
                                        <td>
                                            @if ($borrowing->status === 'returned')
                                                <span class="badge bg-success">Dikembalikan</span>
                                            @else
                                                <span class="badge bg-warning">Dipinjam</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($borrowing->status === 'borrowed')
                                                <form action="{{ route('borrowings.return', $borrowing) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-success">Kembalikan
                                                        Buku</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('borrowings.show', $borrowing) }}"
                                                class="btn btn-sm btn-info">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $borrowings->links() }}
                    </div>

                    <div class="mt-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5>Peraturan Perpustakaan:</h5>
                                <ul>
                                    <li>Buku dapat dipinjam selama 14 hari.</li>
                                    <li>Harap kembalikan buku tepat waktu untuk menghindari denda.</li>
                                    <li>Buku yang rusak atau hilang akan memerlukan penggantian atau pembayaran nilai buku.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <p>Anda saat ini tidak memiliki buku yang dipinjam.</p>
                        <a href="{{ route('books.index') }}" class="btn btn-primary mt-2">Telusuri Buku</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
