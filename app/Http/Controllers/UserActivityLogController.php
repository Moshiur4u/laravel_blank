<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $deletedUsers = UserActivityLog::query()
            ->whereIn('id', UserActivityLog::query()
                ->selectRaw('MAX(id)')
                ->whereNotNull('user_id')
                ->groupBy('user_id'))
            ->whereNotIn('user_id', User::query()->select('id'))
            ->orderBy('user_name')
            ->get(['user_id as id', 'user_name as name', 'user_email as email']);

        $users = $users->concat($deletedUsers)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', Rule::in($users->pluck('id')->all())],
            'event' => ['nullable', 'in:create,update,delete,login,logout'],
        ]);

        $logs = UserActivityLog::query()
            ->forDateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->when($filters['user_id'] ?? null, fn($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['event'] ?? null, fn($query, $event) => $query->where('event', $event))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $selectedUser = $users->firstWhere('id', $filters['user_id'] ?? null);

        return view('backend.user-activity.index', compact('logs', 'users', 'filters', 'selectedUser'));
    }
}
