<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem Manajemen Perpustakaan')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Custom styles -->
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
        }

        .navbar-brand {
            font-weight: 600;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }

        .sidebar .nav-link {
            color: #212529;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
        }

        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }

        .sidebar .nav-link.active {
            background-color: #3490dc;
            color: #fff;
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }

        .footer {
            padding: 1rem 0;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Sistem Manajemen Perpustakaan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @guest
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="nav-link">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('register') }}" class="nav-link">Daftar</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->full_name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Keluar</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <div class="content">
        @auth
            <div class="container-fluid">
                <div class="row">
                    <!-- Sidebar -->
                    <div class="col-md-3 col-lg-2 sidebar py-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}"
                                    class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                                    <i class="fas fa-tachometer-alt"></i> Dasbor
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('books.index') }}"
                                    class="nav-link {{ request()->is('books*') ? 'active' : '' }}">
                                    <i class="fas fa-book"></i> Buku
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('borrowings.index') }}"
                                    class="nav-link {{ request()->is('borrowings') ? 'active' : '' }}">
                                    <i class="fas fa-bookmark"></i> Peminjaman Saya
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('borrowings.history') }}"
                                    class="nav-link {{ request()->is('borrowings/history') ? 'active' : '' }}">
                                    <i class="fas fa-history"></i> Riwayat Peminjaman
                                </a>
                            </li>
                            @if (Auth::user()->isAdmin())
                                <li class="nav-item mt-3">
                                    <h6
                                        class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                                        <span>Admin</span>
                                    </h6>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.dashboard') }}"
                                        class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                                        <i class="fas fa-user-shield"></i> Dasbor Admin
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- Main content -->
                    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                        <div class="content">
                            @if ($errors->any())
                                <div class="container mt-3">
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="container mt-3">
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                </div>
                            @endif

                            @if (session('success'))
                                <div class="container mt-3">
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                </div>
                            @endif
                            @yield('content')
                    </main>
                </div>
            </div>
        @else
            <div class="container py-4">

                <div class="content">
                    @if ($errors->any())
                        <div class="container mt-3">
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="container mt-3">
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="container mt-3">
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif
                    @yield('content')
                </div>
            @endauth
        </div>

        <!-- Footer -->
        <footer class="footer text-center">
            <div class="container">
                <span class="text-muted">&copy; {{ date('Y') }} Sistem Manajemen Perpustakaan. Febriantok
                    Kabisatullah
                    - 411221029.</span>
            </div>
        </footer>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Optional custom scripts -->
        @stack('scripts')
</body>

</html>
