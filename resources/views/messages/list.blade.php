@forelse ($conversations as $c)
    <button type="button" class="conversation" data-id="{{ $c->user->user_id }}">
        <span class="avatar"><i class="bi bi-person-fill"></i></span>
        <span class="grow">
            <strong>{{ $c->user->full_name }}</strong>
            <small class="d-block text-truncate">{{ $c->text }}</small>
        </span>
        <span class="text-end">
            <small>{{ $c->last->created_at->isToday() ? $c->last->created_at->format('g:i A') : $c->last->created_at->format('M j') }}</small>
            @if ($c->unread) <span class="badge unread">{{ $c->unread }}</span> @endif
        </span>
    </button>
@empty
    <p class="text-muted small">No conversations yet.</p>
@endforelse