<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\ValidatorException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Calculate lateness information for a borrowing
     *
     * @param object $borrowing The borrowing object
     * @return object Object containing lateness information
     */
    private function calculateLatenessInfo($borrowing): object
    {
        $result = new \stdClass();
        $result->badge = '';
        $result->text = '';
        $result->status = 'normal';

        try {
            // Convert borrow_date to Carbon object if it's not already
            $borrowDate = is_string($borrowing->borrow_date)
                ? \Carbon\Carbon::parse($borrowing->borrow_date)
                : $borrowing->borrow_date;

            // Calculate due date (borrow date + 14 days)
            $dueDate = $borrowDate->copy()->addDays(14);

            // Get today's date
            $today = \Carbon\Carbon::now();

            // Handle different scenarios based on borrowing status
            if ($borrowing->status === 'returned') {
                $returnDate = is_string($borrowing->return_date)
                    ? \Carbon\Carbon::parse($borrowing->return_date)
                    : $borrowing->return_date;

                if ($returnDate->gt($dueDate)) {
                    // Returned late
                    $daysLate = abs(intval($dueDate->diffInDays($returnDate)));
                    $result->badge = $daysLate > 14 ? 'bg-danger' : 'bg-warning';
                    $result->text = "Telat {$daysLate} hari";
                    $result->status = 'late';
                } else {
                    // Returned on time
                    $result->badge = 'bg-success';
                    $result->text = 'Tepat waktu';
                    $result->status = 'on-time';
                }
            } else {
                // Book has not been returned yet
                if ($today->gt($dueDate)) {
                    // Currently overdue
                    $daysLate = abs(intval($dueDate->diffInDays($today)));
                    $result->badge = $daysLate > 14 ? 'bg-danger' : 'bg-warning';
                    $result->text = "{$daysLate} hari terlambat";
                    $result->status = 'overdue';
                } else {
                    // Not yet overdue - calculate days remaining
                    $daysLeft = abs(intval($dueDate->diffInDays($today)));

                    if ($daysLeft <= 3) {
                        // Approaching due date (warning)
                        $result->badge = 'bg-warning';
                        $result->text = "Hanya {$daysLeft} hari tersisa";
                        $result->status = 'approaching-due';
                    } else {
                        // Still plenty of time
                        $result->badge = 'bg-info';
                        $result->text = "{$daysLeft} hari tersisa";
                        $result->status = 'active';
                    }
                }
            }
        } catch (\Exception $e) {
            $result->badge = 'bg-secondary';
            $result->text = 'Error menghitung';
            $result->status = 'error';
        }

        return $result;
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard(): View
    {
        try {
            $dashboardData = $this->adminService->getDashboardData();

            // Extract data for the view
            $bookStats = $dashboardData['book_stats'] ?? [];
            $totalBooks = $bookStats['total'] ?? 0;
            $borrowingStats = $dashboardData['borrowing_stats'] ?? [];
            $memberStats = $dashboardData['member_stats'] ?? [];
            $activeBorrowings = $borrowingStats['active'] ?? 0;
            $overdueBorrowings = $borrowingStats['overdue'] ?? 0;
            $totalMembers = $memberStats['total'] ?? 0;            // Convert recent borrowings to collection of objects to ensure object access
            $recentBorrowings = collect($dashboardData['recent_borrowings'] ?? [])->map(function ($borrowing) {
                // Convert main borrowing to object
                $borrowingObj = (object) $borrowing;

                // Convert book to object if it exists
                if (isset($borrowingObj->book) && is_array($borrowingObj->book)) {
                    $borrowingObj->book = (object) $borrowingObj->book;
                }

                // Convert member to object if it exists
                if (isset($borrowingObj->member) && is_array($borrowingObj->member)) {
                    $borrowingObj->member = (object) $borrowingObj->member;
                }

                // Ensure dates are properly formatted as strings
                if (isset($borrowingObj->borrow_date)) {
                    $borrowingObj->borrow_date = date('Y-m-d', strtotime($borrowingObj->borrow_date));
                }

                if (isset($borrowingObj->return_date)) {
                    $borrowingObj->return_date = date('Y-m-d', strtotime($borrowingObj->return_date));
                }

                return $borrowingObj;
            });

            // Convert low stock books to collection of objects to ensure object access
            $lowStockBooks = collect($dashboardData['low_stock_books'] ?? [])->map(function ($book) {
                return (object) $book;
            });

            // Convert low stock books array to a paginator for recent books
            $perPage = 5;
            $currentPage = request()->input('page', 1);
            $recentBooksArray = $lowStockBooks;
            $recentBooks = new LengthAwarePaginator(
                $recentBooksArray->forPage($currentPage, $perPage),
                count($recentBooksArray),
                $perPage,
                $currentPage,
                ['path' => request()->url()]
            );

            // System notifications might need to be generated locally
            $notifications = $this->getSystemNotifications();

            return view('admin.dashboard', compact(
                'totalBooks',
                'bookStats',
                'activeBorrowings',
                'overdueBorrowings',
                'totalMembers',
                'recentBorrowings',
                'recentBooks',
                'lowStockBooks',
                'notifications'
            ));
        } catch (Exception $e) {
            Log::error('Error in admin dashboard: ' . $e->getMessage());

            // Provide default values for variables
            $totalBooks = 0;
            $bookStats = [
                'total' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'never_borrowed' => 0
            ];
            $activeBorrowings = 0;
            $overdueBorrowings = 0;
            $totalMembers = 0;
            $recentBorrowings = collect([]);
            $lowStockBooks = collect([]);

            // Empty paginator for recent books
            $recentBooks = new LengthAwarePaginator(
                collect([]),
                0,
                5,
                1,
                ['path' => request()->url()]
            );
            $notifications = [];

            return view('admin.dashboard', compact(
                'totalBooks',
                'bookStats',
                'activeBorrowings',
                'overdueBorrowings',
                'totalMembers',
                'recentBorrowings',
                'recentBooks',
                'lowStockBooks',
                'notifications'
            ))->with('error', 'Gagal memuat data dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Show all members.
     *
     * @return View
     */
    public function members(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 15,
            ];

            $paginatorMembers = $this->adminService->getMembers($filters);

            // Convert array items to objects
            $convertedMembers = collect($paginatorMembers->items())->map(function ($member) {
                return (object) $member;
            });

            // Create a new paginator with converted objects
            $members = new LengthAwarePaginator(
                $convertedMembers,
                $paginatorMembers->total(),
                $paginatorMembers->perPage(),
                $paginatorMembers->currentPage(),
                $paginatorMembers->getOptions()
            );

            return view('admin.members', compact('members'));
        } catch (Exception $e) {
            Log::error('Error in admin members: ' . $e->getMessage());
            $members = new LengthAwarePaginator([], 0, 15);
            return view('admin.members', compact('members'))->with('error', 'Gagal memuat data anggota: ' . $e->getMessage());
        }
    }

    /**
     * Show all borrowings.
     *
     * @return View
     */
    public function borrowings(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'date_range' => $request->input('date_range'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 20,
            ];

            $paginatorBorrowings = $this->adminService->getBorrowings($filters);

            // Get the borrowings as a collection and convert arrays to objects
            $convertedBorrowings = collect($paginatorBorrowings->items())->map(function ($borrowing) {
                // Convert the main borrowing to an object
                $borrowingObj = (object) $borrowing;

                // Convert book to object if it exists
                if (isset($borrowingObj->book) && is_array($borrowingObj->book)) {
                    $borrowingObj->book = (object) $borrowingObj->book;
                }

                // Convert member to object if it exists
                if (isset($borrowingObj->member) && is_array($borrowingObj->member)) {
                    $borrowingObj->member = (object) $borrowingObj->member;
                }

                // Parse dates if they exist
                if (isset($borrowingObj->borrow_date)) {
                    $borrowingObj->borrow_date = \Carbon\Carbon::parse($borrowingObj->borrow_date);
                }

                if (isset($borrowingObj->return_date)) {
                    $borrowingObj->return_date = $borrowingObj->return_date ? \Carbon\Carbon::parse($borrowingObj->return_date) : null;
                }

                // Calculate lateness information and add it to the borrowing object
                $borrowingObj->latenessInfo = $this->calculateLatenessInfo($borrowingObj);

                return $borrowingObj;
            });

            // Create a new paginator with the converted objects
            $borrowings = new LengthAwarePaginator(
                $convertedBorrowings,
                $paginatorBorrowings->total(),
                $paginatorBorrowings->perPage(),
                $paginatorBorrowings->currentPage(),
                $paginatorBorrowings->getOptions()
            );

            return view('admin.borrowings', compact('borrowings'));
        } catch (Exception $e) {
            Log::error('Error in admin borrowings: ' . $e->getMessage());
            $borrowings = new LengthAwarePaginator(collect([]), 0, 20, 1, ['path' => request()->url()]);
            return view('admin.borrowings', compact('borrowings'))->with('error', 'Gagal memuat data peminjaman: ' . $e->getMessage());
        }
    }

    /**
     * Show the reports page.
     *
     * @return View
     */
    public function reports(): View
    {
        return view('admin.reports');
    }

    /**
     * Show the books management page.
     *
     * @return View
     */
    public function books(Request $request): View
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by'),
                'sort_order' => $request->input('sort_order'),
                'page' => $request->input('page', 1),
                'per_page' => 15,
            ];

            $paginatorBooks = $this->adminService->getBooks($filters);

            // Convert array items to objects
            $convertedBooks = collect($paginatorBooks->items())->map(function ($book) {
                return (object) $book;
            });

            // Create a new paginator with converted objects
            $books = new LengthAwarePaginator(
                $convertedBooks,
                $paginatorBooks->total(),
                $paginatorBooks->perPage(),
                $paginatorBooks->currentPage(),
                $paginatorBooks->getOptions()
            );

            return view('admin.books', compact('books'));
        } catch (Exception $e) {
            Log::error('Error in admin books: ' . $e->getMessage());
            $books = new LengthAwarePaginator([], 0, 15);
            return view('admin.books', compact('books'))->with('error', 'Gagal memuat data buku: ' . $e->getMessage());
        }
    }

    /**
     * Show popular books report.
     *
     * @return View
     */
    public function popularBooks(Request $request): View
    {
        try {
            $filters = [
                'period' => $request->input('period'),
                'limit' => $request->input('limit', 15),
            ];

            $booksArray = $this->adminService->getPopularBooks($filters);

            // Convert array items to objects
            $popularBooks = collect($booksArray)->map(function ($book) {
                return (object) $book;
            });

            return view('admin.reports.popular', compact('popularBooks'));
        } catch (Exception $e) {
            Log::error('Error in popular books: ' . $e->getMessage());
            $popularBooks = [];
            return view('admin.reports.popular', compact('popularBooks'))->with('error', 'Gagal memuat data buku populer: ' . $e->getMessage());
        }
    }

    /**
     * Export borrowings to Excel file
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportBorrowingsToExcel(Request $request)
    {
        try {
            // Get filters from request
            $filters = $request->only(['search', 'status', 'date_range']);

            // Get data from API
            $borrowingsData = $this->adminService->getBorrowingsForExport($filters);

            // Generate filename with date
            $filename = 'peminjaman_' . date('Y-m-d_His') . '.xlsx';

            // Return Excel download
            return $this->generateBorrowingsExcel($borrowingsData, $filename);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    /**
     * Export borrowings to PDF file
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportBorrowingsToPdf(Request $request)
    {
        try {
            // Get filters from request
            $filters = $request->only(['search', 'status', 'date_range']);

            // Get data from API
            $borrowingsData = $this->adminService->getBorrowingsForExport($filters);

            // Generate filename with date
            $filename = 'peminjaman_' . date('Y-m-d_His') . '.pdf';

            // Return PDF download
            return $this->generateBorrowingsPdf($borrowingsData, $filename);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    /**
     * Generate Excel file for borrowings data
     *
     * @param array $data
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */    private function generateBorrowingsExcel(array $data, string $filename)
    {
        // Ensure Carbon uses Indonesian locale
        \Carbon\Carbon::setLocale('id');
        // Set PHP locale for formatLocalized method
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

        // Create new Excel spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set column headers
        $sheet->setCellValue('A1', 'No.');
        $sheet->setCellValue('B1', 'Nama Anggota');
        $sheet->setCellValue('C1', 'Judul Buku');
        $sheet->setCellValue('D1', 'Penulis');
        $sheet->setCellValue('E1', 'Tanggal Pinjam');
        $sheet->setCellValue('F1', 'Tanggal Jatuh Tempo');
        $sheet->setCellValue('G1', 'Tanggal Kembali');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'Keterlambatan (Hari)');
        $sheet->setCellValue('J1', 'Denda (Rp)');

        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'DDDDDD',
                ],
            ],
        ];

        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Add data rows
        $row = 2;
        foreach ($data as $index => $borrowing) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $borrowing['member_name']);
            $sheet->setCellValue('C' . $row, $borrowing['book_title']);
            $sheet->setCellValue('D' . $row, $borrowing['book_author']);
            $sheet->setCellValue('E' . $row, $borrowing['borrow_date']); // Now already in dddd, D MMMM Y format
            $sheet->setCellValue('F' . $row, $borrowing['due_date']); // Now already in dddd, D MMMM Y format
            $sheet->setCellValue('G' . $row, $borrowing['return_date'] ?? 'Belum dikembalikan');
            $sheet->setCellValue('H' . $row, $borrowing['status']);
            $sheet->setCellValue('I' . $row, $borrowing['late_days']);
            $sheet->setCellValue('J' . $row, number_format($borrowing['fine_amount'], 0, ',', '.'));

            $row++;
        }

        // Auto size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Save to temporary file
        $tempFilePath = tempnam(sys_get_temp_dir(), 'export_');
        $writer->save($tempFilePath);

        // Return file download response
        return response()->download($tempFilePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate PDF file for borrowings data
     *
     * @param array $data
     * @param string $filename
     * @return \Illuminate\Http\Response
     */    private function generateBorrowingsPdf(array $data, string $filename)
    {
        // Ensure Carbon uses Indonesian locale
        \Carbon\Carbon::setLocale('id');
        // Set PHP locale for formatLocalized method
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

        // Create PDF instance
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.exports.borrowings-pdf', [
            'borrowings' => $data,
            'generatedDate' => $this->formatDateWithoutComma(now()) . ', ' . now()->format('H:i:s'),
            'filters' => request()->only(['search', 'status', 'date_range']),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');

        // Return file download response
        return $pdf->download($filename);
    }

    /**
     * Get system notifications based on dashboard data.
     *
     * @return array
     */
    private function getSystemNotifications(): array
    {
        try {
            $dashboardData = $this->adminService->getDashboardData();
            $notifications = [];

            // Extract data for notifications
            $bookStats = $dashboardData['book_stats'] ?? [];
            $borrowingStats = $dashboardData['borrowing_stats'] ?? [];

            // Check for overdue books
            $overdueCount = $borrowingStats['overdue'] ?? 0;
            if ($overdueCount > 0) {
                $notifications[] = [
                    'title' => 'Buku Terlambat',
                    'message' => "Terdapat {$overdueCount} buku yang terlambat dikembalikan.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            // Check for low stock books
            $lowStockCount = $bookStats['low_stock'] ?? 0;
            if ($lowStockCount > 0) {
                $notifications[] = [
                    'title' => 'Stok Buku Menipis',
                    'message' => "{$lowStockCount} buku memiliki stok yang hampir habis.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            // Check for out of stock books
            $outOfStockCount = $bookStats['out_of_stock'] ?? 0;
            if ($outOfStockCount > 0) {
                $notifications[] = [
                    'title' => 'Buku Habis',
                    'message' => "{$outOfStockCount} buku saat ini tidak tersedia.",
                    'time' => now()->format('Y-m-d H:i')
                ];
            }

            return $notifications;
        } catch (Exception $e) {
            Log::warning('Error generating notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format date without commas in day name
     *
     * @param \Carbon\Carbon|string $date
     * @return string|null
     */
    private function formatDateWithoutComma($date)
    {
        if (!$date) {
            return null;
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        $monthNames = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];

        $dayOfWeek = $dayNames[$date->format('l')] ?? $date->format('l');
        $day = $date->format('j');
        $month = $monthNames[$date->format('F')] ?? $date->format('F');
        $year = $date->format('Y');

        return "$dayOfWeek $day $month $year";
    }
}
