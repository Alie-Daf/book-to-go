<div class="table-card">
    <table class="table mb-0">
        <thead><tr><th>Invoice</th><th>Host</th><th>Type</th><th>Booking</th><th>Amount</th><th>Due</th><th>Status</th><th>PayPal ID</th><th>Proof</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td>#{{ $invoice->invoice_id }}</td>
                    <td>{{ $invoice->host->full_name }}</td>
                    <td>{{ $invoice->invoice_type }}</td>
                    <td>{{ $invoice->booking?->booking_reference ?? '-' }}</td>
                    <td>&#8369;{{ number_format($invoice->amount, 2) }}</td>
                    <td>{{ $invoice->due_date->format('M j, Y') }}</td>
                    <td><span class="status-pill {{ $invoice->status_class }}">{{ $invoice->status }}</span></td>
                    <td>
                        @if ($invoice->status === 'For Verification')
                            <a href="{{ route('files.invoice', $invoice) }}" target="_blank">
                                <img src="{{ route('files.invoice', $invoice) }}" alt="Proof" class="proof-thumb">
                            </a>
                        @endif
                    </td>
                    <td>
                        @if ($invoice->status === 'For Verification')
                            <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.invoices.paid', $invoice) }}"
                                    data-confirm="Mark this invoice as Paid?">Mark as Paid</button>
                            <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.invoices.reject', $invoice) }}"
                                    data-prompt="Short note for the host (why is the proof rejected?)" data-field="notes">Reject Proof</button>
                        @endif
                    </td>
                    <td><small>{{ $invoice->paypal_transaction_id ?? '-' }}</small></td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>