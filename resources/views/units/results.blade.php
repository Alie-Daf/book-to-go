<p class="results-count">{{ $units->count() }} {{ $units->count() === 1 ? 'result' : 'results' }} found</p>

@forelse ($units as $unit)
    @include('partials.unit-card')
@empty
    <div class="card-box text-center text-muted py-5">No units match your search. Try changing the filters.</div>
@endforelse