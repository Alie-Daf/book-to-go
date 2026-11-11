<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\UserDocument;
use App\Models\Invoice;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    // IDs and documents: the owner and the admin only
    public function document(UserDocument $document)
    {
        abort_unless(auth()->user()->role === 'Admin' || $document->user_id == auth()->id(), 403);

        return $this->send($document->file_reference);
    }

        // Unit title and condo permission: the host who owns the unit and the admin
    public function unitDocument(Unit $unit, string $type)
    {
        abort_unless(in_array($type, ['title', 'permission']), 404);
        abort_unless(auth()->user()->role === 'Admin' || $unit->host_id == auth()->id(), 403);

        return $this->send($unit->{$type.'_document'});
    }

    // Proof of payment: the guest, the host of the unit and the admin
    public function proof(Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->role === 'Admin' || $booking->guest_id == $user->user_id || $booking->unit->host_id == $user->user_id, 403);

        return $this->send($booking->payment_proof);
    }

    private function send(?string $path)
    {
        abort_unless($path && Storage::exists($path), 404);

        return Storage::response($path);
    }
        // Proof of an invoice payment: the host who owns it and the admin
    public function invoice(Invoice $invoice)
    {
        abort_unless(auth()->user()->role === 'Admin' || $invoice->host_id == auth()->id(), 403);

        return $this->send($invoice->proof_reference);
    }
}