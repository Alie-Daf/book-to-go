<article class="unit-card">
    <div class="photo">
        <img src="{{ $unit->cover }}" alt="{{ $unit->title }}" onerror="this.remove()">
        <i class="bi bi-image"></i>
    </div>

    <div class="unit-body">
        <div>
            <h3 class="unit-title"><a href="{{ route('units.show', $unit) }}" class="stretched-link">{{ $unit->title }}</a></h3>
            @if (\App\Models\FeaturedListing::isFeatured($unit->unit_id))
                <span class="badge-featured"><i class="bi bi-stars"></i> Featured</span>
            @endif
            <p class="unit-meta mb-1">
                &#8369;{{ number_format($unit->starting_rate) }} / night
                <span class="mx-2">&bull;</span><i class="bi bi-star-fill"></i> {{ $unit->rating_label }}
            </p>
            <small class="text-muted">{{ $unit->category }} &middot; {{ $unit->location }}</small>
        </div>

        <div class="unit-actions">
            <button type="button" class="btn-bookmark" data-url="{{ route('favorites.toggle', $unit) }}"
                    @if (!empty($removeOnUnbookmark)) data-remove="1" @endif>
                <i class="bi {{ in_array($unit->unit_id, $bookmarked) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            </button>
        </div>
    </div>
</article>