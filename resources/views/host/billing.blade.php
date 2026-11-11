@extends('layouts.app')
@section('title', 'Billing')
@section('nav', 'host.billing')

@section('content')
    <h1 class="page-title">Billing</h1>
    
    <div class="stat-grid mb-4">
        <div class="stat-card"><div class="stat-label">Total unpaid</div><div class="stat-value">&#8369;{{ number_format($totalUnpaid, 2) }}</div></div>
        <div class="stat-card"><div class="stat-label">Total overdue</div><div class="stat-value text-danger">&#8369;{{ number_format($totalOverdue, 2) }}</div></div>
        <div class="stat-card"><div class="stat-label">Total paid</div><div class="stat-value">&#8369;{{ number_format($totalPaid, 2) }}</div></div>
    </div>

    <div class="card-box mb-4">
        <h2 class="section-title">Pay the platform with PayPal</h2>
        <p class="payment-box mb-2"><i class="bi bi-paypal"></i> {{ $paypalName }} &middot; {{ $paypalEmail }}</p>
        <p class="mb-0">Send the exact invoice amount to this PayPal account. Then type your PayPal Transaction ID and upload a screenshot of the payment.</p>
    </div>

    <div class="card-box mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>You use <strong>{{ $unitsUsed }}</strong> of <strong>{{ $unitLimit }}</strong> unit slots.</div>
        <button class="btn btn-outline-primary btn-post" data-url="{{ route('host.billing.slot') }}"
                data-confirm="Create an invoice for one extra unit slot?">Buy extra unit slot (&#8369;{{ number_format($slotPrice) }})</button>
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Invoice</th><th>Type</th><th>Booking</th><th>Amount</th><th>Due date</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td>#{{ $invoice->invoice_id }}</td>
                        <td>{{ $invoice->invoice_type }}</td>
                        <td>{{ $invoice->booking?->booking_reference ?? '-' }}</td>
                        <td>&#8369;{{ number_format($invoice->amount, 2) }}</td>
                        <td>{{ $invoice->due_date->format('M j, Y') }}</td>
                        <td><span class="status-pill {{ $invoice->status_class }}">{{ $invoice->status }}</span></td>
                        <td>
                            @if (in_array($invoice->status, ['Unpaid', 'Overdue']))
                                <form class="ajax-form" action="{{ route('host.billing.proof', $invoice) }}" enctype="multipart/form-data">
                                    <input name="paypal_transaction_id" class="form-control form-control-sm mb-1" placeholder="PayPal Transaction ID" maxlength="30" required>
                                    <div class="d-flex gap-1">
                                        <input type="file" name="proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png" required>
                                        <button class="btn btn-sm btn-primary text-nowrap">Upload Proof</button>
                                    </div>
                                </form>
                                @if ($invoice->admin_note)
                                    <small class="text-danger d-block mt-1">Admin note: {{ $invoice->admin_note }}</small>
                                @endif
                            @elseif ($invoice->status === 'For Verification')
                                <small class="text-muted">Waiting for the admin</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-muted small mt-2">Proof of payment: JPG or PNG, up to 2 MB.</p>
@endsection