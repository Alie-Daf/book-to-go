@extends('layouts.auth')
@section('title', 'Create your account')

@section('content')
    <div class="auth-logo"><span class="brand-logo"><i></i><i></i><i></i><i></i></span> BOOK TO GO</div>
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-sub">Join Book to Go</p>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-2">
            <div class="col-6">
                <label class="form-label">First name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required autofocus>
            </div>
            <div class="col-6">
                <label class="form-label">Last name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
            </div>
        </div>
        @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

        <label class="form-label mt-3">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

        <label class="form-label mt-3">Password</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

        <label class="form-label mt-3">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>

        <div class="form-label mt-3">I am a</div>
        <div class="role-tiles">
            @foreach (['Guest', 'Host'] as $role)
                <label class="role-tile">
                    <input class="form-check-input" type="radio" name="role" value="{{ $role }}" @checked(old('role', 'Guest') === $role)> {{ $role }}
                </label>
            @endforeach
        </div>

        <div id="hostDocs" class="mt-3" hidden>
            <div class="form-label">Documents for verification (JPG, PNG or PDF, up to 4 MB each)</div>
            @foreach (\App\Models\UserDocument::HOST_DOCUMENTS as $label)
                <label class="small mt-2">{{ $label }}</label>
                <input type="file" name="documents[{{ $loop->index }}]" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
            @endforeach
            @error('documents.0') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <small class="text-muted d-block mt-2">The admin checks these before your listings can go live. The files are kept private.</small>
        </div>

        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="privacy" id="privacy" value="1" required>
            <label class="form-check-label small" for="privacy">
                I agree that Book To Go may collect and process my personal data, including ID documents, as described under the Data Privacy Act of 2012.
            </label>
        </div>
        @error('privacy') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

        <button class="btn btn-primary w-100 mt-4 py-2">Register</button>
    </form>

    <p class="auth-links">Already have an account? <a href="{{ route('login') }}">Log in</a></p>

    <script>
        // hosts must upload documents, guests do not
        const toggleDocs = () => {
            const isHost = document.querySelector('input[name=role]:checked').value === 'Host';
            document.getElementById('hostDocs').hidden = !isHost;
            document.querySelectorAll('#hostDocs input').forEach(i => { i.required = isHost; i.disabled = !isHost; });
        };
        document.querySelectorAll('input[name=role]').forEach(r => r.addEventListener('change', toggleDocs));
        toggleDocs();
    </script>
@endsection