@extends('layouts.app')
@section('title', 'Verifications')
@section('nav', 'admin.verifications')

@section('content')
    <h1 class="page-title">Verifications</h1>

    <h2 class="section-title">Host verification</h2>
    <div class="table-card mb-5">
        <table class="table mb-0">
            <thead><tr><th>Host</th><th>Submitted</th><th>Documents</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($hosts as $v)
                    <tr>
                        <td>{{ $v->host->full_name }}<small class="d-block text-muted">{{ $v->host->email }}</small></td>
                        <td>{{ $v->submitted_at?->format('M j, Y') }}</td>
                        @php $docs = $v->host->documents->keyBy('document_type'); @endphp
                        <td class="text-start">
                            @foreach (\App\Models\UserDocument::HOST_DOCUMENTS as $type => $label)
                                @if ($doc = $docs->get($type))
                                    <div><i class="bi bi-check-circle-fill text-success"></i>
                                        <a href="{{ route('files.document', $doc) }}" target="_blank">{{ $label }}</a>
                                        <small class="text-muted">({{ $doc->verification_status }})</small></div>
                                @else
                                    <div class="text-danger"><i class="bi bi-x-circle-fill"></i> {{ $label }} (missing)</div>
                                @endif
                            @endforeach
                        </td>
                        <td><span class="status-pill status-{{ $v->status }}">{{ $v->status }}</span></td>
                        <td>
                            @if ($v->status === 'Pending')
                                <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.verifications.decide', [$v, 'approve']) }}"
                                        data-confirm="Give this host the Verified badge?">Approve</button>
                                <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.verifications.decide', [$v, 'reject']) }}"
                                        data-prompt="Why is this rejected?" data-field="notes">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No host requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="section-title">Guest valid IDs (age verification)</h2>
    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Guest</th><th>File</th><th>Uploaded</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($ids as $doc)
                    <tr>
                        <td>{{ $doc->user->full_name }}</td>
                        <td><a href="{{ route('files.document', $doc) }}" target="_blank">View ID</a></td>
                        <td>{{ $doc->uploaded_at?->format('M j, Y') }}</td>
                        <td><span class="status-pill status-{{ $doc->verification_status }}">{{ $doc->verification_status }}</span></td>
                        <td>
                            @if ($doc->verification_status === 'Pending')
                                <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.documents.decide', [$doc, 'approve']) }}">Approve</button>
                                <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.documents.decide', [$doc, 'reject']) }}"
                                        data-prompt="Why is this rejected?" data-field="notes">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No guest IDs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection