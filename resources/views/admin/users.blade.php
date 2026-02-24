@extends('layouts.app')
@section('title', 'Manage Users')
@section('page-title', 'User Management')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">All Users</h2>
        <div class="flex gap-2">
            <a href="{{ route('admin.stats') }}" class="btn-secondary text-sm"><i class="fas fa-chart-bar mr-1"></i>Analytics</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Provider</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $user->avatar_url }}" class="w-9 h-9 rounded-full" alt="">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                @csrf
                                <select name="role" onchange="this.form.submit()"
                                        class="text-xs border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200"
                                        {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                    @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge bg-gray-100 dark:bg-gray-700 text-gray-500 text-xs">{{ $user->provider ?? 'local' }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                @csrf
                                <button class="text-xs {{ $user->is_active ? 'text-red-500' : 'text-green-500' }}">
                                    {{ $user->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
