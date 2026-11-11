<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\BillingService;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with(['host', 'booking'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->type, fn ($q, $v) => $q->where('invoice_type', $v))
            ->when($request->q, fn ($q, $v) => $q->whereHas('host', fn ($h) => $h->search($v)))
            ->latest('invoice_id')->get();

        return $request->ajax()
            ? view('admin.invoices-table', compact('invoices'))
            : view('admin.invoices', compact('invoices'));
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->status === 'For Verification', 422, 'There is no proof to verify.');

        BillingService::markPaid($invoice, $request->user()->user_id);

        return ['message' => 'Invoice marked as Paid.'];
    }

    public function rejectProof(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->status === 'For Verification', 422, 'There is no proof to reject.');
        $data = $request->validate(['notes' => 'required|max:500']);

        BillingService::rejectProof($invoice, $data['notes']);

        return ['message' => 'Proof rejected. The host can upload a new one.'];
    }
}