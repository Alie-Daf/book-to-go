<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivacyController extends Controller
{
    // Right to access: download everything we keep about the user
    public function export(Request $request)
    {
        $user = $request->user();

        $data = [
            'profile' => $user->only(['first_name', 'last_name', 'email', 'phone_number', 'role', 'created_at', 'privacy_consent_at']),
            'documents' => $user->documents()->get(['document_type', 'verification_status', 'uploaded_at']),
            'bookings' => $user->bookings()->get(['booking_reference', 'check_in_date', 'check_out_date', 'total_amount', 'booking_status']),
            'reviews' => $user->reviews()->get(['unit_id', 'rating', 'comment', 'created_at']),
            'messages_sent' => Message::where('sender_id', $user->user_id)->get(['receiver_id', 'body', 'created_at']),
            'invoices' => Invoice::where('host_id', $user->user_id)->get(['invoice_type', 'amount', 'status', 'due_date']),
        ];

        return response()->json($data, 200, ['Content-Disposition' => 'attachment; filename="my-book-to-go-data.json"']);
    }

    // Right to erasure: personal data is erased. Bookings and invoices stay (without the name) for accounting.
    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        // an account with unfinished business cannot be deleted
        $upcoming = fn ($query) => $query->whereIn('booking_status', ['Paid', 'Confirmed'])->where('check_out_date', '>=', today());

        $busy = $user->role === 'Host'
            ? $user->invoices()->whereIn('status', ['Unpaid', 'For Verification', 'Overdue'])->exists()
                || $upcoming(Booking::whereHas('unit', fn ($q) => $q->where('host_id', $user->user_id)))->exists()
            : $upcoming($user->bookings())->exists();

        abort_if($busy, 422, 'You still have unpaid invoices or upcoming bookings. Finish them first.');
        abort_if($user->role === 'Admin', 403, 'Admin accounts cannot be deleted here.');

        foreach ($user->documents as $document) {
            Storage::delete($document->file_reference);
        }
        $user->documents()->delete();
        $user->bookmarks()->detach();
        $user->units()->update(['listing_status' => 'Inactive']);
        Message::where('sender_id', $user->user_id)->update(['body' => '[deleted]']);
        $user->notifications()->delete();

        $user->update([
            'first_name' => 'Deleted',
            'last_name' => 'User '.$user->user_id,
            'email' => "deleted-{$user->user_id}@booktogo.invalid",
            'phone_number' => null,
            'payment_instructions' => null,
            'password_hash' => Hash::make(Str::random(40)),
            'account_status' => 'Removed',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ['message' => 'Your account was deleted.', 'redirect' => route('login')];
    }
}