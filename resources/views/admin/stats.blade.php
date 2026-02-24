@extends('layouts.app')
@section('title', 'Analytics')
@section('page-title', 'Library Analytics')

@section('content')
<div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Library Analytics</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Users', 'value' => $stats['total_users'], 'icon' => 'users', 'color' => 'blue'],
            ['label' => 'Total Books', 'value' => $stats['total_books'], 'icon' => 'books', 'color' => 'green'],
            ['label' => 'Active Checkouts', 'value' => $stats['active_checkouts'], 'icon' => 'book-reader', 'color' => 'orange'],
            ['label' => 'Overdue', 'value' => $stats['overdue'], 'icon' => 'exclamation-triangle', 'color' => 'red'],
        ] as $s)
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">{{ $s['label'] }}</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $s['value'] }}</p>
                </div>
                <div class="w-12 h-12 bg-{{ $s['color'] }}-100 dark:bg-{{ $s['color'] }}-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-{{ $s['icon'] }} text-{{ $s['color'] }}-500 text-xl"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Most Borrowed Books</h3>
            <div class="space-y-3">
                @foreach($stats['most_borrowed'] as $i => $book)
                <div class="flex items-center gap-3">
                    <span class="w-6 text-center text-sm font-bold text-gray-400">{{ $i + 1 }}</span>
                    <img src="{{ $book->cover_url }}" class="w-10 h-14 object-cover rounded">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $book->title }}</p>
                        <p class="text-xs text-gray-400">{{ $book->author }}</p>
                    </div>
                    <span class="badge bg-blue-100 text-blue-700">{{ $book->times_borrowed }}x</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Checkouts This Year (by Month)</h3>
            <div class="space-y-2">
                @php $maxCount = $stats['checkouts_by_month']->max('count') ?: 1 @endphp
                @foreach($stats['checkouts_by_month'] as $m)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-8">{{ DateTime::createFromFormat('!m', $m->month)->format('M') }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div class="h-4 bg-blue-500 rounded-full transition-all"
                             style="width: {{ ($m->count / $maxCount) * 100 }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300 w-6 text-right">{{ $m->count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
