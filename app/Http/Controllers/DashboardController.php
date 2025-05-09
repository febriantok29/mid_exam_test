<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $recentBooks = Book::orderBy('created_at', 'desc')
            ->paginate(5);

        return view('dashboard', compact('recentBooks'));
    }
}
