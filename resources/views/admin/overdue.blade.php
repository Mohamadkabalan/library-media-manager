@extends('layouts.app')
@section('title', 'Overdue Books')
@section('page-title', 'Overdue Books Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Overdue Books</h2>
            <p class="text-sm text-gray-500">{{ $overdueCheckouts->total() }} overdue checkouts</p>
        </div>
        <a href="{{ route('admin.users') }}" class="btn-secondary text-sm">
            <i class="fas fa-users mr-1"></i> Users
        </a>
    </div>

    @if($overdueCheckouts->isEmpty())
    <div class="card p-12 text-center">
        <div class="text-5xl mb-4">✅</div>
        <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">No overdue books!</h3>
        <p class="text-gray-400 mt-2">All checkouts are within their due dates.</p>
    </div>
    @else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-red-50 dark:bg-red-900/20 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Book</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Borrower</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Days Overdue</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($overdueCheckouts as $checkout)
                    @php $daysOverdue = now()->diffInDays($checkout->due_date) @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $checkout->book->cover_url }}" class="w-10 h-14 object-cover rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $checkout->book->title }}</p>
                                    <p class="text-xs text-gray-400">{{ $checkout->book->author }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <img src="{{ $checkout->user->avatar_url }}" class="w-8 h-8 rounded-full">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $checkout->user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $checkout->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-red-600">{{ $checkout->due_date->format('M d, Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge bg-red-100 text-red-700 font-bold">{{ $daysOverdue }} days</span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('books.checkin', $checkout->book) }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $checkout->user_id }}">
                                <button class="text-xs px-3 py-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition font-medium">
                                    <i class="fas fa-undo mr-1"></i>Check In
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $overdueCheckouts->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
