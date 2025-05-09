<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Member;
use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BorrowingSeeder extends Seeder
{
    public function run(): void
    {
        $books = Book::all();
        $members = Member::where('role', 'member')->get();
        
        // Create various borrowing scenarios
        
        // 1. Create returned books
        foreach ($members->take(5) as $member) {
            // Each member has some returned books
            for ($i = 0; $i < 3; $i++) {
                $book = $books->random();
                $borrowDate = Carbon::now()->subDays(rand(20, 40));
                
                Borrowing::create([
                    'member_id' => $member->member_id,
                    'book_id' => $book->book_id,
                    'borrow_date' => $borrowDate,
                    'return_date' => $borrowDate->copy()->addDays(rand(1, 14)),
                    'status' => 'returned'
                ]);
            }
        }

        // 2. Create currently borrowed books (not returned)
        foreach ($members as $member) {
            // Each member has some current borrows
            for ($i = 0; $i < rand(1, 3); $i++) {
                $book = $books->where('quantity_available', '>', 0)->random();
                $borrowDate = Carbon::now()->subDays(rand(1, 13)); // Within last 2 weeks
                
                Borrowing::create([
                    'member_id' => $member->member_id,
                    'book_id' => $book->book_id,
                    'borrow_date' => $borrowDate,
                    'status' => 'borrowed'
                ]);

                // Update book quantity
                $book->decrement('quantity_available');
            }
        }

        // 3. Create some overdue books (borrowed more than 14 days ago, not returned)
        foreach ($members->take(3) as $member) {
            $book = $books->where('quantity_available', '>', 0)->random();
            $borrowDate = Carbon::now()->subDays(rand(15, 30));
            
            Borrowing::create([
                'member_id' => $member->member_id,
                'book_id' => $book->book_id,
                'borrow_date' => $borrowDate,
                'status' => 'borrowed'
            ]);

            // Update book quantity
            $book->decrement('quantity_available');
        }

        // 4. Create some books that are completely borrowed out
        $booksToEmpty = $books->where('quantity_available', '>', 0)->take(3);
        foreach ($booksToEmpty as $book) {
            while ($book->quantity_available > 0) {
                $member = $members->random();
                $borrowDate = Carbon::now()->subDays(rand(1, 10));

                Borrowing::create([
                    'member_id' => $member->member_id,
                    'book_id' => $book->book_id,
                    'borrow_date' => $borrowDate,
                    'status' => 'borrowed'
                ]);

                // Update book quantity
                $book->decrement('quantity_available');
            }
        }
    }
}
