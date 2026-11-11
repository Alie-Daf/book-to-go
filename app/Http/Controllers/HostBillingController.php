<?php

namespace App\Http\Controllers;

use App\Models\FeeSetting;
use App\Models\Invoice;
use App\Services\BillingService;
use Illuminate\Http\Request;

class HostBillingController extends Controller
{
    public function index(Request $request)
    {
        $host = $request->user();
        $invoices = $host->invoices()->with('booking')->latest('invoice_id')->get();
        $total = fn (array $statuses) => $invoices->whereIn('status', $statuses)->sum('amount');

        return view('host.billing', [
            'invoices' => $invoices,
            'totalUnpaid' => $total(['Unpaid', 'For Verification']),
            'totalOverdue' => $total(['Overdue']),
            'totalPaid' => $total(['Paid']),
            'paypalName' => FeeSetting::get('platform_paypal_name'),
            'paypalEmail' => FeeSetting::get('platform_paypal_email'),
            'unitsUsed' => $host->units()->where('listing_status', '!=', 'Inactive')->count(),
            'unitLimit' => $host->unitLimit(),
            'slotPrice' => FeeSetting::get('unit_slot_price'),
        ]);
    }

    // The host sends the money to the platform's GCash, then uploads the proof here
    // The host sends the money to the platform's PayPal, then enters the Transaction ID and uploads a screenshot
    public function uploadProof(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->host_id == $request->user()->user_id, 403);     // only his own invoices
        abort_unless(in_array($invoice->status, ['Unpaid', 'Overdue']), 422, 'This invoice cannot accept a proof right now.');

        $request->merge(['paypal_transaction_id' => strtoupper(trim((string) $request->paypal_transaction_id))]);

        // checked on the server, not only in the browser
        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'paypal_transaction_id' => 'required|alpha_num|between:10,30|unique:invoices,paypal_transaction_id',
        ], ['paypal_transaction_id.unique' => 'This PayPal Transaction ID was already used for another invoice.']);

        $invoice->update([
            'proof_reference' => $request->file('proof')->store('invoice-proofs'),   // private folder, random file name
            'paypal_transaction_id' => $request->paypal_transaction_id,
            'status' => 'For Verification',
            'admin_note' => null,
        ]);

        return ['message' => 'Proof uploaded. The admin will verify it.'];
    }

    // Optional extra unit slot: creates an invoice, the slot opens when the admin marks it Paid
    public function buyUnitSlot(Request $request)
    {
        BillingService::createInvoice($request->user()->user_id, 'Unit Slot', FeeSetting::get('unit_slot_price', 99));

        return ['message' => 'Invoice created. Pay it to unlock one more unit.'];
    }
}