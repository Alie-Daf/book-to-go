@forelse ($reviews as $review)
    <div class="card-box mb-3">
        <div class="d-flex justify-content-between">
            <div class="d-flex gap-3">
                <span class="avatar">{{ $review->guest->initials }}</span>
                <div>
                    <strong>{{ $review->guest->full_name }}</strong>
                    <div class="text-muted small">{{ $review->unit->title }} &middot; {{ $review->created_at->format('m/d/Y') }}</div>
                </div>
            </div>
            <div class="text-primary">
                @for ($i = 1; $i <= 5; $i++) <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i> @endfor
            </div>
        </div>

        <p class="mt-3 mb-2">{{ $review->comment }}</p>

        @if ($review->host_reply)
            <div class="reply-box mb-2"><strong>Your reply:</strong> {{ $review->host_reply }}</div>
        @endif

        <div class="d-flex justify-content-between">
            <button class="btn btn-sm btn-outline-primary btn-post" data-url="{{ route('host.reviews.reply', $review) }}"
                    data-prompt="Write your reply" data-field="reply">{{ $review->host_reply ? 'Edit reply' : 'Reply' }}</button>
            <button class="btn btn-link btn-sm text-danger btn-post" data-url="{{ route('host.reviews.report', $review) }}"
                    data-prompt="Why are you reporting this review?" data-field="reason">Report</button>
        </div>
    </div>
@empty
    <div class="card-box text-center text-muted py-5">No reviews found.</div>
@endforelse