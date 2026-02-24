<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Book;
use App\Models\BookCheckout;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole('admin')) {
                abort(403, 'Admin access required.');
            }
            return $next($request);
        });
    }

    public function users(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, fn($q, $s) => $q->where('name', 'LIKE', "%{$s}%")->orWhere('email', 'LIKE', "%{$s}%"))
            ->latest()
            ->paginate(20);

        $roles = Role::all();
        return view('admin.users', compact('users', 'roles'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => 'required|exists:roles,name']);

        // Prevent demoting last admin
        if ($user->hasRole('admin') && $data['role'] !== 'admin') {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot remove the last admin.');
            }
        }

        $user->syncRoles([$data['role']]);
        return back()->with('success', "Role updated to {$data['role']}.");
    }

    public function toggleUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot deactivate yourself.');
        }
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }

    public function overdueReport()
    {
        $overdueCheckouts = BookCheckout::with(['book', 'user'])
            ->where('status', 'active')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->paginate(20);

        return view('admin.overdue', compact('overdueCheckouts'));
    }

    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_books' => Book::count(),
            'active_checkouts' => BookCheckout::where('status', 'active')->count(),
            'overdue' => BookCheckout::where('status', 'active')->where('due_date', '<', now())->count(),
            'most_borrowed' => Book::orderByDesc('times_borrowed')->limit(10)->get(),
            'checkouts_by_month' => BookCheckout::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        return view('admin.stats', compact('stats'));
    }
}
