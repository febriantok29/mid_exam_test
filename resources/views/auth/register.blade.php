@extends('layouts.app')

@section('title', 'Daftar - Sistem Manajemen Perpustakaan')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Daftar</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror"
                                id="full_name" name="full_name" value="{{ old('full_name') }}" required autofocus>
                            @error('full_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                id="username" name="username" value="{{ old('username') }}" required>
                            @error('username')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="password-icon"></i>
                                </button>
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye" id="confirm-password-icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Daftar</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <p>Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');

            togglePassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                // Toggle icon
                passwordIcon.classList.toggle('bi-eye');
                passwordIcon.classList.toggle('bi-eye-slash');
            });

            // Confirm password toggle functionality
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPassword = document.getElementById('password_confirmation');
            const confirmPasswordIcon = document.getElementById('confirm-password-icon');

            toggleConfirmPassword.addEventListener('click', function() {
                // Toggle type attribute
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);

                // Toggle icon
                confirmPasswordIcon.classList.toggle('bi-eye');
                confirmPasswordIcon.classList.toggle('bi-eye-slash');
            });

            // Pre-validate form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                const pass = document.getElementById('password').value;
                const confirmPass = document.getElementById('password_confirmation').value;

                // Just for debugging purposes
                console.log('Password:', pass);
                console.log('Confirmation:', confirmPass);

                // Make sure passwords match before submitting
                if (pass !== confirmPass) {
                    e.preventDefault();
                    alert('Kata sandi dan konfirmasi kata sandi tidak cocok. Silakan periksa kembali.');
                    return false;
                }
            });
        });
    </script>
    @endpush
@endsection
