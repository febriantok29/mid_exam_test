@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <h1 class="mb-4">Riwayat Peminjaman Saya</h1>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Filter Riwayat</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('borrowings.history') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari (Judul Buku)</label>
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
                            <option value="last_3_months" {{ request('date_range') == 'last_3_months' ? 'selected' : '' }}>
                                3 Bulan Terakhir</option>
                            <option value="last_6_months" {{ request('date_range') == 'last_6_months' ? 'selected' : '' }}>
                                6 Bulan Terakhir</option>
                            <option value="last_year" {{ request('date_range') == 'last_year' ? 'selected' : '' }}>Tahun
                                Lalu
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Terapkan Filter</button>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('borrowings.history') }}" class="btn btn-secondary">Atur Ulang Filter</a>
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
                                    <th>Judul Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status</th>
                                    <th>Keterlambatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($borrowings as $borrowing)
                                    <tr>
                                        <td>
                                            <a
                                                href="{{ route('books.show', $borrowing->book) }}">{{ $borrowing->book->title }}</a>
                                        </td>
                                        <td>{{ $borrowing->formatted_borrow_date }}</td>
                                        <td>
                                            @if ($borrowing->return_date)
                                                {{ $borrowing->formatted_return_date }}
                                            @else
                                                <span class="text-muted">Belum dikembalikan</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $borrowing->display_status['class'] }}">
                                                {{ $borrowing->display_status['text'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $borrowing->late_status['class'] }}">
                                                {{ $borrowing->late_status['text'] }}
                                            </span>
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
                        <p>Tidak ditemukan catatan peminjaman.</p>
                        @if (request()->has('search') || request()->has('status') || request()->has('date_range'))
                            <a href="{{ route('borrowings.history') }}" class="btn btn-secondary mt-2">Hapus Filter</a>
                        @else
                            <a href="{{ route('books.index') }}" class="btn btn-primary mt-2">Telusuri Buku</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <div class="card">
                <div class="card-header">
                    <h5>Statistik Bacaan</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Buku Dipinjam:</strong> {{ $totalBorrowed }}</p>
                    <p><strong>Buku yang Sedang Dipinjam:</strong> {{ $currentlyBorrowed }}</p>
                    <p><strong>Buku Terlambat Dikembalikan:</strong> {{ $returnedLate }}</p>
                    <p><strong>Rata-rata Waktu Peminjaman:</strong> {{ round($averageBorrowDays) }} hari</p>
                </div>
            </div>
        </div>
    </div>
@endsection
