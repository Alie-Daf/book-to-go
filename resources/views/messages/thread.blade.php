<div class="chat-header">
    <span class="avatar"><i class="bi bi-person-fill"></i></span>
    <strong>{{ $user->full_name }}</strong>
    @if ($user->is_verified) <span class="verified ms-2"><i class="bi bi-patch-check-fill"></i> Verified</span> @endif
</div>

<div class="chat-messages">
    @forelse ($messages as $m)
        <div class="bubble-row {{ $m->sender_id == auth()->id() ? 'mine' : '' }}">
            <div class="bubble">{{ $m->display }}<small>{{ $m->created_at->format('M j, g:i A') }}</small></div>
        </div>
    @empty
        <p class="chat-empty">No messages yet. Say hello!</p>
    @endforelse
</div>