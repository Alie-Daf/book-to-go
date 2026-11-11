<div class="table-card">
    <table class="table mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role }}</td>
                    <td><span class="status-pill status-{{ $user->account_status }}">{{ $user->account_status }}</span></td>
                    <td>
                        @if ($user->user_id != auth()->id())
                            @if ($user->account_status !== 'Active')
                                <button class="btn btn-sm btn-outline-primary btn-post" data-url="{{ route('admin.users.status', [$user, 'Active']) }}">Activate</button>
                            @else
                                <button class="btn btn-sm btn-outline-secondary btn-post" data-url="{{ route('admin.users.status', [$user, 'Suspended']) }}"
                                        data-confirm="Suspend this account?">Suspend</button>
                            @endif
                            @if ($user->account_status !== 'Removed')
                                <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.users.status', [$user, 'Removed']) }}"
                                        data-confirm="Remove this account?">Remove</button>
                            @endif
                        @else
                            <span class="text-muted small">You</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>