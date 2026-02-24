<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $librarian = Role::firstOrCreate(['name' => 'librarian']);
        $member = Role::firstOrCreate(['name' => 'member']);

        // Create admin user
        $adminUser = User::firstOrCreate(
          ['email' => 'admin@library.local'],
          [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
          ]
        );
        $adminUser->assignRole('admin');

        // Create librarian
        $librarianUser = User::firstOrCreate(
          ['email' => 'librarian@library.local'],
          [
            'name' => 'Jane Librarian',
            'password' => Hash::make('password'),
            'provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
          ]
        );
        $librarianUser->assignRole('librarian');

        // Create test member
        $memberUser = User::firstOrCreate(
          ['email' => 'member@library.local'],
          [
            'name' => 'John Reader',
            'password' => Hash::make('password'),
            'provider' => 'local',
            'is_active' => true,
            'email_verified_at' => now(),
          ]
        );
        $memberUser->assignRole('member');

        // Seed sample books
        $books = [
          ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'genre' => 'Fantasy', 'isbn' => '978-0-261-10221-7', 'publication_year' => 1937, 'total_pages' => 310, 'publisher' => 'Allen & Unwin', 'total_copies' => 3, 'available_copies' => 3, 'description' => 'A fantasy novel about Bilbo Baggins, a hobbit who embarks on an unexpected journey.'],
          ['title' => '1984', 'author' => 'George Orwell', 'genre' => 'Dystopian Fiction', 'isbn' => '978-0-452-28423-4', 'publication_year' => 1949, 'total_pages' => 328, 'publisher' => 'Secker & Warburg', 'total_copies' => 2, 'available_copies' => 2, 'description' => 'A dystopian novel set in a totalitarian society controlled by Big Brother.'],
          ['title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'genre' => 'Southern Gothic', 'isbn' => '978-0-06-112008-4', 'publication_year' => 1960, 'total_pages' => 281, 'publisher' => 'J. B. Lippincott & Co.', 'total_copies' => 2, 'available_copies' => 2, 'description' => 'A Pulitzer Prize-winning novel about racial injustice and moral growth in the American South.'],
          ['title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'genre' => 'Literary Fiction', 'isbn' => '978-0-7432-7356-5', 'publication_year' => 1925, 'total_pages' => 180, 'publisher' => 'Charles Scribner\'s Sons', 'total_copies' => 2, 'available_copies' => 2, 'description' => 'A critique of the American Dream set in the Roaring Twenties.'],
          ['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'Science Fiction', 'isbn' => '978-0-441-17271-9', 'publication_year' => 1965, 'total_pages' => 688, 'publisher' => 'Chilton Books', 'total_copies' => 3, 'available_copies' => 3, 'description' => 'An epic science fiction novel about politics, religion, and ecology on the desert planet Arrakis.'],
          ['title' => 'Pride and Prejudice', 'author' => 'Jane Austen', 'genre' => 'Romance', 'isbn' => '978-0-14-143951-8', 'publication_year' => 1813, 'total_pages' => 432, 'publisher' => 'T. Egerton', 'total_copies' => 2, 'available_copies' => 2, 'description' => 'A romantic novel following Elizabeth Bennet and her complicated relationship with Mr. Darcy.'],
          ['title' => 'The Catcher in the Rye', 'author' => 'J.D. Salinger', 'genre' => 'Literary Fiction', 'isbn' => '978-0-316-76948-0', 'publication_year' => 1951, 'total_pages' => 277, 'publisher' => 'Little, Brown and Company', 'total_copies' => 1, 'available_copies' => 1, 'description' => 'A coming-of-age story narrated by teenager Holden Caulfield.'],
          ['title' => 'Brave New World', 'author' => 'Aldous Huxley', 'genre' => 'Dystopian Fiction', 'isbn' => '978-0-06-085052-4', 'publication_year' => 1932, 'total_pages' => 311, 'publisher' => 'Chatto & Windus', 'total_copies' => 2, 'available_copies' => 2, 'description' => 'A dystopian novel set in a futuristic World State of genetically modified citizens.'],
        ];

        foreach ($books as $bookData) {
            $bookData['added_by'] = $adminUser->id;
            $bookData['status'] = 'active';
            $bookData['times_borrowed'] = rand(0, 25);
            $bookData['location'] = 'Section ' . chr(rand(65, 69)) . rand(1, 20);
            Book::firstOrCreate(['isbn' => $bookData['isbn']], $bookData);
        }

        $this->command->info('✅ Seeded roles, users, and ' . count($books) . ' sample books.');
        $this->command->info('');
        $this->command->info('Demo accounts:');
        $this->command->info('  Admin:     admin@library.local / password');
        $this->command->info('  Librarian: librarian@library.local / password');
        $this->command->info('  Member:    member@library.local / password');
    }
}
