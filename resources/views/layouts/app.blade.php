<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', sidebarOpen: false }"
      :class="{ 'dark': darkMode }" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .gradient-brand { background: linear-gradient(135deg, #1d4ed8 0%, #7c3aed 100%); }
        .glass { backdrop-filter: blur(12px); background: rgba(255,255,255,0.85); }
        .dark .glass { background: rgba(17,24,39,0.85); }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200; }
        .sidebar-link:hover { @apply bg-white/10 text-white; }
        .sidebar-link.active { @apply bg-white/20 text-white font-semibold; }
        .card { @apply bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700; }
        .btn-primary { @apply inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all duration-200 shadow-sm hover:shadow-md; }
        .btn-secondary { @apply inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl transition-all duration-200; }
        .btn-danger { @apply inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-all duration-200; }
        .form-input { @apply w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all; }
        .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5; }
        .badge { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">

<div class="flex h-full min-h-screen" x-data="{ sidebarOpen: window.innerWidth >= 1024 }">

    {{-- Sidebar --}}
    <aside x-show="sidebarOpen" x-cloak
           class="fixed inset-y-0 left-0 z-50 w-64 flex-shrink-0 gradient-brand shadow-2xl transition-transform lg:relative lg:translate-x-0"
           @keydown.escape.window="sidebarOpen = false">

        <div class="flex flex-col h-full">
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book-open text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-sm leading-none">Library &</h1>
                    <h1 class="text-blue-200 font-bold text-sm leading-tight">Media Manager</h1>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <p class="px-4 py-1.5 text-xs font-semibold text-blue-200 uppercase tracking-wider">Main</p>

                <a href="{{ route('dashboard') }}"
                   class="sidebar-link text-blue-100 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>

                <p class="px-4 py-1.5 mt-3 text-xs font-semibold text-blue-200 uppercase tracking-wider">📚 Library</p>

                <a href="{{ route('books.index') }}"
                   class="sidebar-link text-blue-100 {{ request()->routeIs('books.*') ? 'active' : '' }}">
                    <i class="fas fa-books w-5"></i> Browse Books
                </a>

                @can('create', App\Models\Book::class)
                <a href="{{ route('books.create') }}"
                   class="sidebar-link text-blue-100 {{ request()->routeIs('books.create') ? 'active' : '' }}">
                    <i class="fas fa-plus-circle w-5"></i> Add Book
                </a>
                @endcan

                <p class="px-4 py-1.5 mt-3 text-xs font-semibold text-blue-200 uppercase tracking-wider">🎬 My Media</p>

                <a href="{{ route('media.index') }}"
                   class="sidebar-link text-blue-100 {{ request()->routeIs('media.*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group w-5"></i> My Collection
                </a>
                <a href="{{ route('media.create') }}"
                   class="sidebar-link text-blue-100">
                    <i class="fas fa-plus w-5"></i> Add Media
                </a>

                <p class="px-4 py-1.5 mt-3 text-xs font-semibold text-blue-200 uppercase tracking-wider">🤖 AI Tools</p>

                <a href="{{ route('ai.chat') }}"
                   class="sidebar-link text-blue-100 {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                    <i class="fas fa-robot w-5"></i> AI Assistant
                </a>

                @if(auth()->user()->is_librarian)
                <p class="px-4 py-1.5 mt-3 text-xs font-semibold text-blue-200 uppercase tracking-wider">⚙️ Management</p>
                <a href="{{ route('admin.overdue') }}"
                   class="sidebar-link text-blue-100">
                    <i class="fas fa-exclamation-triangle w-5"></i> Overdue
                </a>
                @if(auth()->user()->is_admin)
                <a href="{{ route('admin.users') }}"
                   class="sidebar-link text-blue-100">
                    <i class="fas fa-users-cog w-5"></i> Users
                </a>
                <a href="{{ route('admin.stats') }}"
                   class="sidebar-link text-blue-100">
                    <i class="fas fa-chart-bar w-5"></i> Analytics
                </a>
                @endif
                @endif
            </nav>

            {{-- User --}}
            <div class="p-4 border-t border-white/10" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-3 w-full text-left">
                    <img src="{{ auth()->user()->avatar_url }}" class="w-9 h-9 rounded-full ring-2 ring-white/30" alt="">
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-blue-200 text-xs truncate">{{ auth()->user()->roles->first()?->name ?? 'member' }}</p>
                    </div>
                    <i class="fas fa-chevron-up text-blue-200 text-xs transition-transform" :class="{ 'rotate-180': !open }"></i>
                </button>
                <div x-show="open" x-cloak class="mt-2 space-y-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-link text-blue-100 w-full">
                            <i class="fas fa-sign-out-alt w-5"></i> Sign Out
                        </button>
                    </form>
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="sidebar-link text-blue-100 w-full">
                        <i class="fas fa-moon w-5" x-show="!darkMode"></i>
                        <i class="fas fa-sun w-5" x-show="darkMode" x-cloak></i>
                        <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
                    </button>
                </div>
            </div>
        </div>
    </aside>

    {{-- Overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top Bar --}}
        <header class="glass border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center gap-4 sticky top-0 z-30">
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-bars text-gray-600 dark:text-gray-300"></i>
            </button>

            <div class="flex-1">
                <h2 class="text-base font-semibold text-gray-800 dark:text-white">@yield('page-title', 'Dashboard')</h2>
            </div>

            {{-- Quick search --}}
            <form action="{{ route('books.index') }}" method="GET" class="hidden md:flex items-center">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input name="search" type="search" placeholder="Search books..."
                           class="pl-9 pr-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 w-48 focus:w-64 transition-all"
                           value="{{ request()->get('search') }}">
                </div>
            </form>

            {{-- Notifications --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 relative">
                    <i class="fas fa-bell text-gray-600 dark:text-gray-300"></i>
                    @if(auth()->user()->activeCheckouts()->where('due_date', '<', now()->addDays(2))->count() > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    @endif
                </button>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 4000)"
             class="mx-4 mt-4 px-4 py-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 rounded-xl flex items-center gap-3">
            <i class="fas fa-check-circle text-green-500"></i>
            {{ session('success') }}
            <button @click="show = false" class="ml-auto text-green-500"><i class="fas fa-times"></i></button>
        </div>
        @endif
        @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-cloak x-init="setTimeout(() => show = false, 5000)"
             class="mx-4 mt-4 px-4 py-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 rounded-xl flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            {{ session('error') }}
            <button @click="show = false" class="ml-auto text-red-500"><i class="fas fa-times"></i></button>
        </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 overflow-auto p-4 md:p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
