<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
            [
                'isbn' => '9780132350884',
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'year_published' => 2008,
                'quantity_available' => 0, // All copies borrowed
            ],
            [
                'isbn' => '9780201633610',
                'title' => 'Design Patterns',
                'author' => 'Erich Gamma',
                'year_published' => 1994,
                'quantity_available' => 5,
            ],
            [
                'isbn' => '9780134685991',
                'title' => 'Effective Java',
                'author' => 'Joshua Bloch',
                'year_published' => 2017,
                'quantity_available' => 3,
            ],
            [
                'isbn' => '9781449331818',
                'title' => 'Learning JavaScript Design Patterns',
                'author' => 'Addy Osmani',
                'year_published' => 2012,
                'quantity_available' => 1, // Almost out of stock
            ],
            [
                'isbn' => '9780596007126',
                'title' => 'Head First Design Patterns',
                'author' => 'Eric Freeman',
                'year_published' => 2004,
                'quantity_available' => 4,
            ],
            // Add more books for pagination testing
            [
                'isbn' => '9780596516178',
                'title' => 'JavaScript: The Good Parts',
                'author' => 'Douglas Crockford',
                'year_published' => 2008,
                'quantity_available' => 2,
            ],
            [
                'isbn' => '9781491904244',
                'title' => 'You Don\'t Know JS',
                'author' => 'Kyle Simpson',
                'year_published' => 2015,
                'quantity_available' => 0, // All copies borrowed
            ],
            [
                'isbn' => '9780735619678',
                'title' => 'Code Complete',
                'author' => 'Steve McConnell',
                'year_published' => 2004,
                'quantity_available' => 3,
            ],
            [
                'isbn' => '9781617292392',
                'title' => 'Laravel: Up & Running',
                'author' => 'Matt Stauffer',
                'year_published' => 2019,
                'quantity_available' => 2,
            ],
            [
                'isbn' => '9781617295850',
                'title' => 'Laravel in Action',
                'author' => 'Taylor Otwell',
                'year_published' => 2020,
                'quantity_available' => 0, // All copies borrowed
            ],
            [
                'isbn' => '9781484273050',
                'title' => 'Modern PHP',
                'author' => 'Josh Lockhart',
                'year_published' => 2021,
                'quantity_available' => 4,
            ],
            [
                'isbn' => '9781491918661',
                'title' => 'Learning React',
                'author' => 'Alex Banks',
                'year_published' => 2020,
                'quantity_available' => 2,
            ],
            [
                'isbn' => '9781789615623',
                'title' => 'Full-Stack React Projects',
                'author' => 'Shama Hoque',
                'year_published' => 2020,
                'quantity_available' => 1,
            ],
            [
                'isbn' => '9781788834094',
                'title' => 'TypeScript 4 Design Patterns',
                'author' => 'Vitor Brandao',
                'year_published' => 2021,
                'quantity_available' => 3,
            ],
            [
                'isbn' => '9781800564039',
                'title' => 'Database Design Patterns',
                'author' => 'Michael Hunger',
                'year_published' => 2022,
                'quantity_available' => 2,
            ],
        ];

        foreach ($books as $book) {
            Book::create($book);
        }
    }
}
