<header class="stepper">
    @foreach (['Reservation', 'Guests', 'Done'] as $i => $label)
        <div class="step {{ $step == $i + 1 ? 'active' : '' }} {{ $step > $i + 1 ? 'done' : '' }}">
            <span class="step-circle">{{ $i + 1 }}</span> {{ $label }}
        </div>
    @endforeach
</header>