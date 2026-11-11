@extends('layouts.app')
@section('title', 'Audit Log')
@section('nav', 'admin.audit')

@section('content')
    <h1 class="page-title">Audit Log</h1>


    <p class="text-muted">The last 200 important actions: who did what, and when.</p>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $log->user?->full_name ?? 'System' }}<small class="d-block text-muted">{{ $log->user?->role }}</small></td>
                        <td class="text-start">{{ $log->action }}</td>
                        <td>{{ $log->target_type }} {{ $log->target_id ? '#'.$log->target_id : '' }}</td>
                        <td class="text-start">{{ $log->details }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No actions recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection