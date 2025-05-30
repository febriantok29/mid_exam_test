@extends('layouts.app')

@section('title', 'Tambah Buku Baru - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('books.index') }}">Buku</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Buku Baru</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title h3">Tambah Buku Baru</h1>
            </div>
            <div class="card-body">
                <form action="{{ route('books.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Judul <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                    id="title" name="title" value="{{ old('title') }}" required maxlength="150">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="author" class="form-label">Penulis</label>
                                <input type="text" class="form-control @error('author') is-invalid @enderror"
                                    id="author" name="author" value="{{ old('author') }}" maxlength="100">
                                @error('author')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('isbn') is-invalid @enderror"
                                    id="isbn" name="isbn" value="{{ old('isbn') }}" required maxlength="20">
                                @error('isbn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year_published" class="form-label">Tahun Terbit</label>
                                <input type="number" min="1800" max="{{ date('Y') }}"
                                    class="form-control @error('year_published') is-invalid @enderror" id="year_published"
                                    name="year_published" value="{{ old('year_published') }}">
                                @error('year_published')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="quantity_available" class="form-label">Jumlah Tersedia <span
                                        class="text-danger">*</span></label>
                                <input type="number" min="0"
                                    class="form-control @error('quantity_available') is-invalid @enderror"
                                    id="quantity_available" name="quantity_available"
                                    value="{{ old('quantity_available', 0) }}" required>
                                @error('quantity_available')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Simpan Buku</button>
                            <a href="{{ route('books.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
