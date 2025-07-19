@extends('layouts.app')

@section('title', 'Tambah Buku - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Daftar Buku</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Buku</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title h3">Tambah Buku Baru</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('books.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control @error('isbn') is-invalid @enderror" id="isbn" name="isbn"
                            value="{{ old('isbn') }}" required>
                        @error('isbn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                            name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="author" class="form-label">Penulis</label>
                        <input type="text" class="form-control @error('author') is-invalid @enderror" id="author"
                            name="author" value="{{ old('author') }}">
                        @error('author')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="year_published" class="form-label">Tahun Terbit</label>
                        <input type="number" class="form-control @error('year_published') is-invalid @enderror"
                            id="year_published" name="year_published" value="{{ old('year_published') }}" min="1800"
                            max="{{ date('Y') }}">
                        @error('year_published')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="quantity_available" class="form-label">Jumlah Tersedia</label>
                        <input type="number" class="form-control @error('quantity_available') is-invalid @enderror"
                            id="quantity_available" name="quantity_available" value="{{ old('quantity_available', 1) }}"
                            required min="0">
                        @error('quantity_available')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('books.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
