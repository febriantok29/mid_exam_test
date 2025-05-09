@extends('layouts.app')

@section('title', 'Kelola Peminjaman - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Kelola Peminjaman</h1>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Filter Peminjaman</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.borrowings') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Cari (Judul/Anggota)</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua</option>
                            <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Dikembalikan
                            </option>
                            <option value="borrowed" {{ request('status') == 'borrowed' ? 'selected' : '' }}>Masih Dipinjam
                            </option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Terlambat
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_range" class="form-label">Rentang Waktu</label>
                        <select class="form-select" id="date_range" name="date_range">
                            <option value="">Semua Waktu</option>
                            <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Bulan
                                Lalu</option>
                            <option value="last_3_months" {{ request('date_range') == 'last_3_months' ? 'selected' : '' }}>3
                                Bulan Terakhir</option>
                            <option value="last_6_months" {{ request('date_range') == 'last_6_months' ? 'selected' : '' }}>6
                                Bulan Terakhir</option>
                            <option value="last_year" {{ request('date_range') == 'last_year' ? 'selected' : '' }}>Tahun
                                Lalu</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        <a href="{{ route('admin.borrowings') }}" class="btn btn-secondary ms-2">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if ($borrowings->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status</th>
                                    <th>Keterlambatan</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($borrowings as $borrowing)
                                    <tr>
                                        <td>{{ $borrowing->member->full_name }}</td>
                                        <td>
                                            <a
                                                href="{{ route('books.show', $borrowing->book) }}">{{ $borrowing->book->title }}</a>
                                        </td>
                                        <td>{{ $borrowing->borrow_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if ($borrowing->return_date)
                                                {{ $borrowing->return_date->format('Y-m-d') }}
                                            @else
                                                <span class="text-muted">Belum dikembalikan</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($borrowing->status === 'returned')
                                                <span class="badge bg-success">Dikembalikan</span>
                                            @else
                                                <span class="badge bg-warning">Dipinjam</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($borrowing->status === 'borrowed' && now()->diffInDays($borrowing->borrow_date) > 14)
                                                <span class="badge bg-danger">
                                                    {{ now()->diffInDays($borrowing->borrow_date) - 14 }} hari
                                                </span>
                                            @elseif ($borrowing->return_date && $borrowing->return_date->diffInDays($borrowing->borrow_date) > 14)
                                                <span class="badge bg-danger">
                                                    {{ $borrowing->return_date->diffInDays($borrowing->borrow_date) - 14 }}
                                                    hari
                                                </span>
                                            @else
                                                <span class="badge bg-success">Tepat Waktu</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($borrowing->status === 'borrowed')
                                                <form action="{{ route('borrowings.return', $borrowing) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-success">Kembalikan</button>
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

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <p class="mb-0">Menampilkan {{ $borrowings->firstItem() ?? 0 }} hingga
                                {{ $borrowings->lastItem() ?? 0 }} dari {{ $borrowings->total() }} data</p>
                        </div>
                        <div>
                            {{ $borrowings->appends(request()->query())->links() }}
                        </div>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <p>Tidak ada data peminjaman yang ditemukan.</p>
                        @if (request()->has('search') || request()->has('status') || request()->has('date_range'))
                            <a href="{{ route('admin.borrowings') }}" class="btn btn-secondary mt-2">Hapus Filter</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
