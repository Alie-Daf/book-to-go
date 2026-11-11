<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\FeaturedListing;
use App\Models\FeeSetting;
use App\Models\Invoice;
use App\Models\UnitAvailability;
use App\Models\User;
use App\Notifications\AppNotice;
use Illuminate\Support\Facades\DB;

class BillingService
{
    // ---------- creating invoices ----------
    // The only place that creates an invoice, so the due date is always worked out the same way
    public static function createInvoice(int $hostId, string $type, $amount, ?int $bookingId = null): Invoice
    {
        return Invoice::create([
            'host_id' => $hostId,
            'booking_id' => $bookingId,
            'invoice_type' => $type,
            'amount' => $amount,
            'status' => 'Unpaid',
            'due_date' => today()->addDays((int) FeeSetting::get('invoice_due_days', 3)),
        ]);
    }

    // ---------- EVENT: host clicks "Confirm Payment Received" ----------
    public static function confirmBooking(Booking $booking): Invoice
    {
        self::checkOverdue();   // make sure the hold status is up to date

        $host = User::find($booking->unit->host_id);
        abort_if($host->is_on_hold, 422, User::HOLD_MESSAGE);

        // everything below is saved together or not at all
        $invoice = DB::transaction(function () use ($booking) {
            // lock the row: a second click waits here, then sees the booking is already Confirmed
            $booking = Booking::whereKey($booking->booking_id)->lockForUpdate()->first();
            abort_unless($booking->booking_status === 'Paid', 422, 'This booking was already confirmed.');

            $commission = round($booking->total_amount * (float) FeeSetting::get('commission_rate'), 2);

            // 1. confirm, save the commission and make the door passcode
            $booking->update([
                'booking_status' => 'Confirmed',
                'responded_at' => now(),
                'host_response' => 'Payment received.',
                'commission_amount' => $commission,
                'door_passcode' => $booking->door_passcode ?? (string) random_int(100000, 999999),
            ]);

            // 2. the dates were held when the guest booked; make sure they are marked Booked
            UnitAvailability::firstOrCreate(
                ['unit_id' => $booking->unit_id, 'notes' => $booking->booking_reference],
                [
                    'start_date' => $booking->check_in_date->toDateString(),
                    'end_date' => $booking->check_out_date->copy()->subDay()->toDateString(),
                    'availability_status' => 'Booked',
                ]
            );

            // 3. the commission invoice (the unique index also stops duplicates)
            return self::createInvoice($booking->unit->host_id, 'Commission', $commission, $booking->booking_id);
        });

        $booking->guest->notify(new AppNotice(
            "Your booking {$booking->booking_reference} is confirmed. Your door passcode is ready.",
            route('bookings.show', $booking, false)
        ));
        AuditLog::record('Payment confirmed, commission invoice created', $invoice);

        return $invoice;
    }

    // ---------- EVENT: a booking is cancelled ----------
    // $reason: 'host' (host cancels), 'guest' (guest cancels before paying) or 'expired' (no proof in time).
    // Host cancels: the commission invoice is cancelled too.
    // Guest cancels: we do not touch any invoice (no-refund policy; a guest can only cancel before paying anyway).
    public static function cancelBooking(Booking $booking, string $reason = 'host'): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update(['booking_status' => 'Cancelled']);

            // the dates are free again
            UnitAvailability::where('unit_id', $booking->unit_id)->where('notes', $booking->booking_reference)->delete();

            Invoice::where('booking_id', $booking->booking_id)->where('invoice_type', 'Commission')
                ->whereIn('status', ['Unpaid', 'Overdue', 'For Verification'])->update(['status' => 'Cancelled']);
        });

        $message = match ($reason) {
            'expired' => "Your booking {$booking->booking_reference} expired because no proof of payment was uploaded in time.",
            'host' => "The host cancelled your booking {$booking->booking_reference}.",
            default => null,
        };
        if ($message) {
            $booking->guest->notify(new AppNotice($message, route('bookings.show', $booking, false)));
        }

        AuditLog::record("Booking cancelled ($reason)", $booking);
        self::liftHoldIfClear($booking->unit->host);
    }

    // ---------- EVENT: admin clicks "Mark as Paid" ----------
    public static function markPaid(Invoice $invoice, int $adminId): void
    {
        DB::transaction(function () use ($invoice, $adminId) {
            $invoice->update(['status' => 'Paid', 'verified_by' => $adminId, 'verified_at' => now(), 'admin_note' => null]);

            // unlock what the host paid for
            match ($invoice->invoice_type) {
                'Image Pack' => self::startImagePack($invoice),
                'Unit Slot' => User::whereKey($invoice->host_id)->increment('extra_unit_slots'),
                default => null,    // Commission: nothing else. Featured: now waits for the admin to approve it.
            };
        });

        $invoice->host->notify(new AppNotice("Invoice #{$invoice->invoice_id} was verified as paid. Thank you!", route('host.billing', [], false)));
        AuditLog::record('Invoice marked as paid', $invoice);
        self::liftHoldIfClear($invoice->host);
    }

    private static function startImagePack(Invoice $invoice): void
    {
        $row = $invoice->unitImagePack;
        $row->update(['start_date' => today(), 'end_date' => today()->addDays($row->pack->duration_days)]);
    }

    // ---------- EVENT: admin clicks "Reject Proof" ----------
    public static function rejectProof(Invoice $invoice, string $note): void
    {
        $invoice->update([
            'status' => $invoice->due_date->lt(today()) ? 'Overdue' : 'Unpaid',
            'proof_reference' => null,
            'paypal_transaction_id' => null,
            'admin_note' => $note,
        ]);

        $invoice->host->notify(new AppNotice("The proof for invoice #{$invoice->invoice_id} was rejected: $note", route('host.billing', [], false)));
        AuditLog::record('Invoice proof rejected', $invoice, $note);
        self::checkOverdue();
    }

    // The hold is lifted when the host has no Overdue invoice left
    public static function liftHoldIfClear(User $host): void
    {
        if (Invoice::where('host_id', $host->user_id)->where('status', 'Overdue')->exists()) {
            return;
        }

        $lifted = User::whereKey($host->user_id)->where('account_status', 'Hold')->update(['account_status' => 'Active']);

        if ($lifted) {
            $host->notify(new AppNotice('Your account hold was lifted. You can confirm bookings again.', route('host.billing', [], false)));
        }
    }

    // ---------- EVENT: date check (login, admin dashboard, confirm payment, and every hour by the scheduler) ----------
    public static function checkOverdue(): void
    {
        $today = today()->toDateString();

        // 1. unpaid invoices past their due date become Overdue
        Invoice::where('status', 'Unpaid')->where('due_date', '<', $today)->update(['status' => 'Overdue']);

        // 2. hosts with an overdue invoice go on Hold (suspended or removed accounts are left alone)
        User::whereIn('user_id', Invoice::where('status', 'Overdue')->pluck('host_id'))
            ->where('account_status', 'Active')->get()
            ->each(function ($host) {
                $host->update(['account_status' => 'Hold']);
                $host->notify(new AppNotice('Your account is on hold because of an overdue invoice. Settle it in Billing.', route('host.billing', [], false)));
            });

        // 3. featured listings start when their date arrives and expire when it ends.
        // While a host is on Hold his featured listings are paused: FeaturedListing::activeUnitIds() skips them.
        FeaturedListing::where('featured_status', 'Pending')->whereNotNull('approved_by')
            ->where('start_date', '<=', $today)->where('end_date', '>=', $today)->update(['featured_status' => 'Active']);

        FeaturedListing::whereIn('featured_status', ['Pending', 'Active'])->whereNotNull('approved_by')
            ->where('end_date', '<', $today)->update(['featured_status' => 'Expired']);

        // 4. bookings that never got a proof of payment are released (the dates open again)
        $limit = now()->subHours((int) FeeSetting::get('booking_hold_hours', 24));
        Booking::with('unit.host', 'guest')->where('booking_status', 'Pending')->where('requested_at', '<', $limit)
            ->get()->each(fn ($booking) => self::cancelBooking($booking, 'expired'));
    }
}