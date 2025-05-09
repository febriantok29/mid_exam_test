@extends('layouts.app')

@section('title', 'Detail Peminjaman - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Detail Peminjaman</h1>
            <a href="{{ route('borrowings.index') }}" class="btn btn-secondary">Kembali ke Peminjaman</a>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Peminjaman #{{ $borrowing->borrowing_id }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Buku</th>
                                <td>{{ $borrowing->book->title }}</td>
                            </tr>
                            @if (Auth::user()->isAdmin())
                                <tr>
                                    <th>Anggota</th>
                                    <td>{{ $borrowing->member->name }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Tanggal Pinjam</th>
                                <td>{{ $borrowing->formatted_borrow_date }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal Kembali</th>
                                <td>{{ $borrowing->formatted_return_date ?? 'Belum dikembalikan' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge {{ $borrowing->display_status['class'] }}">
                                        {{ $borrowing->display_status['text'] }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    @if ($borrowing->status === 'borrowed')
                        <form action="{{ route('borrowings.return', $borrowing) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success">Kembalikan Buku</button>
                        </form>
                    @endif
                    <a href="{{ route('books.show', $borrowing->book) }}" class="btn btn-info ms-2">Lihat Detail Buku</a>
                </div>
            </div>
        </div>
    </div>
@endsection
