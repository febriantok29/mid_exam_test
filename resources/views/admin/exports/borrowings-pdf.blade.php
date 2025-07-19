<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Peminjaman</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 15px;
            font-size: 10px;
            text-align: right;
            font-style: italic;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .filter-info {
            font-size: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>LAPORAN PEMINJAMAN BUKU PERPUSTAKAAN</h1>

    <div class="filter-info">
        @if(!empty($filters['search']))
            <p>Pencarian: {{ $filters['search'] }}</p>
        @endif

        @if(!empty($filters['status']))
            <p>Status:
                @if($filters['status'] == 'borrowed')
                    Masih Dipinjam
                @elseif($filters['status'] == 'returned')
                    Dikembalikan
                @elseif($filters['status'] == 'overdue')
                    Terlambat
                @endif
            </p>
        @endif

        @if(!empty($filters['date_range']))
            <p>Rentang Waktu:
                @if($filters['date_range'] == 'last_month')
                    Bulan Terakhir
                @elseif($filters['date_range'] == 'last_3_months')
                    3 Bulan Terakhir
                @elseif($filters['date_range'] == 'last_6_months')
                    6 Bulan Terakhir
                @elseif($filters['date_range'] == 'last_year')
                    Tahun Terakhir
                @endif
            </p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">No.</th>
                <th>Nama Anggota</th>
                <th>Judul Buku</th>
                <th>Penulis</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Jatuh Tempo</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
                <th class="text-right">Keterlambatan</th>
                <th class="text-right">Denda (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($borrowings as $index => $borrowing)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $borrowing['member_name'] }}</td>
                    <td>{{ $borrowing['book_title'] }}</td>
                    <td>{{ $borrowing['book_author'] }}</td>
                    <td>{{ $borrowing['borrow_date'] }}</td>
                    <td>{{ $borrowing['due_date'] }}</td>
                    <td>{{ $borrowing['return_date'] ?? 'Belum dikembalikan' }}</td>
                    <td>{{ $borrowing['status'] }}</td>
                    <td class="text-right">{{ $borrowing['lateness_text'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format($borrowing['fine_amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Diekspor pada: {{ $generatedDate }}
    </div>
</body>
</html>
