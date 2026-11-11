<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    // Tabs: All / Guests / Hosts (?role=Guest)
    public function index(Request $request)
    {
        $role = $request->query('role');

        $users = User::query()
            ->when($role, fn ($q, $v) => $q->where('role', $v))
            ->when($request->q, fn ($q, $v) => $q->search($v))
            ->latest('user_id')->get();

        return $request->ajax()
            ? view('admin.users-table', compact('users'))
            : view('admin.users', compact('users', 'role'));
    }

    public function store(Request $request)
    {
        $name = 'regex:/^[\pL][\pL\s.\'\-]*$/u';

        $data = $request->validate([
            'first_name' => ['required', 'max:80', $name, Rule::unique('users', 'first_name')->where('last_name', $request->last_name)],
            'last_name' => ['required', 'max:80', $name],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:Admin,Host,Guest',
        ], ['first_name.unique' => 'Someone with this first and last name already exists.']);

        $user = User::create([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        AuditLog::record('User added', $user, $data['role']);

        return ['message' => 'User added.'];
    }

    // Suspend, remove or activate an account
    public function status(Request $request, User $user, string $status)
    {
        abort_unless(in_array($status, ['Active', 'Suspended', 'Removed']), 404);
        abort_if($user->user_id == $request->user()->user_id, 422, 'You cannot change your own account.');

        $user->update(['account_status' => $status]);

        // a suspended or removed host's listings go offline
        if ($user->role === 'Host' && $status !== 'Active') {
            $user->units()->update(['listing_status' => 'Inactive']);
        }
        AuditLog::record("Account set to $status", $user);
        
        return ['message' => "{$user->full_name} is now {$status}."];
    }
}