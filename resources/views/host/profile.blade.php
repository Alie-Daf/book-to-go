@extends('layouts.app')
@section('title', 'Profile')
@section('nav', 'host.profile')

@section('content')
    @php $status = $host->verification?->status ?? 'Not submitted'; @endphp

    <div class="text-center mb-4">
        <span class="avatar avatar-xl">{{ $host->initials }}</span>
        <h1 class="page-title mt-3 mb-0">{{ $host->full_name }}</h1>
        <p class="text-muted mb-1">{{ $host->email }}</p>
        @if ($host->is_verified)
            <span class="verified"><i class="bi bi-patch-check-fill"></i> Verified host</span>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-box">
                <h2 class="section-title">Contact and payment instructions</h2>
                <form class="ajax-form" action="{{ route('host.profile.update') }}">
                    <label class="form-label">Phone number</label>
                    <input name="phone_number" class="form-control mb-3" value="{{ $host->phone_number }}">

                    <label class="form-label">Payment instructions for guests (PayPal email, GCash).</label>
                    <textarea name="payment_instructions" rows="3" class="form-control mb-3" required>{{ $host->payment_instructions }}</textarea>
                    <small class="text-muted d-block mb-3">Guests will see this when they pay for a booking.</small>

                    <button class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="section-title mb-0">Verification</h2>
                    <span class="status-pill status-{{ $status }}">{{ $status }}</span>
                </div>
                <p class="text-muted">Upload all 4 documents. The admin reviews them and gives you the Verified badge. You can add units after that.</p>

                @if ($status === 'Rejected' && $host->verification->reviewer_notes)
                    <div class="alert alert-danger">{{ $host->verification->reviewer_notes }}</div>
                @endif

                <form class="ajax-form" action="{{ route('host.profile.document') }}" enctype="multipart/form-data">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="document_type" class="form-select">
                                @foreach (\App\Models\UserDocument::HOST_DOCUMENTS as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                <button class="btn btn-primary">Upload</button>
                            </div>
                        </div>
                    </div>
                </form>

                @php $docs = $host->documents->keyBy('document_type'); @endphp
                @foreach (\App\Models\UserDocument::HOST_DOCUMENTS as $type => $label)
                    <div class="d-flex justify-content-between border-top mt-3 pt-2">
                        @if ($doc = $docs->get($type))
                            <a href="{{ route('files.document', $doc) }}" target="_blank">{{ $label }}</a>
                            <span class="text-muted small">{{ $doc->verification_status }}</span>
                        @else
                            <span>{{ $label }}</span>
                            <span class="text-danger small">Missing</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@include('partials.privacy-card')

@endsection