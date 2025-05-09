@extends('layouts.app')

@section('title', 'Dasbor Admin - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <h1 class="mb-4">Dasbor Admin</h1>

        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Buku</h5>
                        <p class="display-4">{{ $totalBooks }}</p>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('books.index') }}" class="btn btn-sm btn-primary">Kelola Buku</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Anggota</h5>
                        <p class="display-4">{{ $totalMembers }}</p>
                    </div>
                    <div class="card-footer">
                        <a href="#" class="btn btn-sm btn-primary">Kelola Anggota</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Peminjaman Aktif</h5>
                        <p class="display-4">{{ $activeBorrowings }}</p>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.borrowings') }}" class="btn btn-sm btn-primary">Lihat Peminjaman</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title">Keterlambatan</h5>
                        <p class="display-4 {{ $overdueBorrowings > 0 ? 'text-danger' : '' }}">{{ $overdueBorrowings }}</p>
                    </div>
                    <div class="card-footer">
                        <a href="#" class="btn btn-sm btn-danger">Kelola Keterlambatan</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Buku Terbaru</h5>
                        <a href="{{ route('books.create') }}" class="btn btn-sm btn-primary">Tambah Buku</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Penulis</th>
                                        <th>ISBN</th>
                                        <th>Stok</th>
                                        <th>Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentBooks as $book)
                                        <tr>
                                            <td>{{ $book->title }}</td>
                                            <td>{{ $book->author ?? 'Tidak Diketahui' }}</td>
                                            <td>{{ $book->isbn }}</td>
                                            <td>
                                                @if ($book->quantity_available > 0)
                                                    <span class="badge bg-success">{{ $book->quantity_available }}</span>
                                                @else
                                                    <span class="badge bg-danger">Habis</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('books.show', $book) }}" class="btn btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('books.edit', $book) }}" class="btn btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada buku.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $recentBooks->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Buku yang Perlu Restok</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Tersedia</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lowStockBooks as $book)
                                        <tr>
                                            <td>{{ $book->title }}</td>
                                            <td>{{ $book->quantity_available }}</td>
                                            <td>
                                                @if ($book->quantity_available == 0)
                                                    <span class="badge bg-danger">Habis</span>
                                                @else
                                                    <span class="badge bg-warning">Stok Rendah</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">Semua stok buku mencukupi</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('books.index') }}" class="btn btn-sm btn-primary">Kelola Semua Buku</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Peminjaman Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Anggota</th>
                                        <th>Buku</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentBorrowings as $borrowing)
                                        <tr>
                                            <td>{{ $borrowing->member->full_name }}</td>
                                            <td>{{ $borrowing->book->title }}</td>
                                            <td>{{ $borrowing->borrow_date }}</td>
                                            <td>
                                                @if ($borrowing->status === 'returned')
                                                    <span class="badge bg-success">Dikembalikan</span>
                                                @else
                                                    @if ($borrowing->return_date && $borrowing->return_date < now())
                                                        <span class="badge bg-danger">Terlambat</span>
                                                    @else
                                                        <span class="badge bg-warning">Dipinjam</span>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada peminjaman terbaru</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.borrowings') }}" class="btn btn-sm btn-primary">Lihat Semua Peminjaman</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Tindakan Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('books.create') }}" class="btn btn-primary w-100">
                                    <i class="fas fa-plus-circle me-2"></i> Tambah Buku Baru
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i> Tambah Anggota Baru
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-info w-100">
                                    <i class="fas fa-book-reader me-2"></i> Catatan Peminjaman Baru
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-warning w-100">
                                    <i class="fas fa-chart-bar me-2"></i> Lihat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
