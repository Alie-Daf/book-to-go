<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\FeaturedListing;
use App\Models\FeeSetting;
use App\Models\HostVerification;
use App\Models\ImagePack;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\UnitImagePack;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'Admin')->first();
        $jose = User::where('email', 'jose@booktogo.test')->first();
        $rate = (float) FeeSetting::get('commission_rate');
                // Jose is verified (hosts need verification before they can list units)
        HostVerification::updateOrCreate(['host_id' => $jose->user_id],
            ['status' => 'Verified', 'reviewed_by' => $admin->user_id, 'reviewed_at' => now()]);

        // Lena just registered with all 4 documents and waits for the admin (demo of host verification)
        $lena = $this->makeUser('Lena Ramos', 'lena@booktogo.test', 'Host', 'PayPal lena@example.com');
        foreach (array_keys(UserDocument::HOST_DOCUMENTS) as $type) {
            UserDocument::create(['user_id' => $lena->user_id, 'document_type' => $type,
                'file_reference' => 'host-documents/sample.png', 'verification_status' => 'Pending']);
        }
        HostVerification::create(['host_id' => $lena->user_id, 'status' => 'Pending', 'submitted_at' => now()]);

        // ---------- extra people ----------
        $ana = $this->makeUser('Ana Lim', 'ana@booktogo.test', 'Guest');
        $ben = $this->makeUser('Ben Cruz', 'ben@booktogo.test', 'Guest');
        $carlo = $this->makeUser('Carlo Bautista', 'carlo@booktogo.test', 'Host', 'GCash 0919 555 1212 (Carlo Bautista)');

        foreach ([$ana, $ben] as $guest) {
            UserDocument::create(['user_id' => $guest->user_id, 'document_type' => 'Valid ID', 'file_reference' => 'valid-ids/sample.png', 'verification_status' => 'Approved']);
        }
        HostVerification::create(['host_id' => $carlo->user_id, 'reviewed_by' => $admin->user_id, 'status' => 'Verified', 'reviewed_at' => now()]);

        // ---------- Carlo: an overdue invoice, so his account is on Hold ----------
        $bayview = Unit::create([
            'host_id' => $carlo->user_id, 'title' => 'Bayview Condo', 'category' => 'Condo',
            'description' => 'A bright condo with a bay view, close to shops and transport.',
            'address_line' => 'Roxas Boulevard', 'city' => 'Pasay City', 'province' => 'Metro Manila', 'max_guests' => 4,
            'check_in_time' => '14:00:00', 'check_out_time' => '12:00:00', 'house_rules' => 'No smoking. No parties.',
            'listing_status' => 'Approved', 'approved_by' => $admin->user_id, 'approved_at' => now(),
        ]);
        $bayview->rates()->create(['guest_count' => 2, 'nightly_rate' => 1500, 'valid_from' => '2026-01-01']);
        $bayview->images()->create(['image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=900&q=70', 'alt_text' => 'Bayview Condo', 'display_order' => 0]);

        $old = $this->book($bayview, $ana, -20, 2, 'Confirmed', '111222');
        $this->invoice($carlo, 'Commission', $old->total_amount * $rate, 'Overdue', -5, $old);
        $carlo->update(['account_status' => 'Hold']);
        $this->book($bayview, $ben, 15, 2, 'Paid')
            ->update(['paid_at' => now()->subDays(3), 'payment_reference' => 'DEMO12345678']);   // waiting for Carlo, who is blocked

        // ---------- Jose: invoices in the other statuses ----------
        $upcoming = Booking::where('door_passcode', '482913')->first();
        $this->invoice($jose, 'Commission', $upcoming->total_amount * $rate, 'Unpaid', 2, $upcoming);

        $done = Booking::whereHas('unit', fn ($q) => $q->where('title', 'Family Apartment'))->where('booking_status', 'Confirmed')->first();
        $this->invoice($jose, 'Commission', $done->total_amount * $rate, 'Paid', -10, $done);

        $garden = Unit::where('title', 'Garden House')->first();
        $cancelled = $this->book($garden, $ben, 25, 2, 'Cancelled');
        $this->invoice($jose, 'Commission', $cancelled->total_amount * $rate, 'Cancelled', 3, $cancelled);

        // an image pack waiting for the admin to verify the payment
        $plus = ImagePack::where('pack_name', 'Plus')->first();
        $packInvoice = $this->invoice($jose, 'Image Pack', $plus->price, 'For Verification', 2);
        UnitImagePack::create([
            'unit_id' => Unit::where('title', 'Spacious House')->value('unit_id'),
            'pack_id' => $plus->pack_id, 'invoice_id' => $packInvoice->invoice_id,
        ]);

        // a paid and approved Featured listing
        $featuredInvoice = $this->invoice($jose, 'Featured', FeeSetting::get('featured_price_week'), 'Paid', -3);
        FeaturedListing::create([
            'unit_id' => Unit::where('title', 'Loft Apartment')->value('unit_id'),
            'invoice_id' => $featuredInvoice->invoice_id,
            'start_date' => today()->subDay()->toDateString(), 'end_date' => today()->addDays(5)->toDateString(),
            'featured_status' => 'Active', 'approved_by' => $admin->user_id,
        ]);
                // every seeded unit already has sample ownership documents
        Unit::whereNull('title_document')->update(['title_document' => 'unit-documents/sample.pdf']);
        Unit::where('category', 'Condo')->whereNull('permission_document')->update(['permission_document' => 'unit-documents/sample.pdf']);
    }

    private function makeUser(string $name, string $email, string $role, ?string $payment = null): User
    {
        [$first, $last] = explode(' ', $name, 2);

        return User::create([
            'first_name' => $first, 'last_name' => $last, 'email' => $email,
            'password_hash' => Hash::make('password'), 'role' => $role, 'payment_instructions' => $payment,
        ]);
    }

    private function invoice(User $host, string $type, $amount, string $status, int $dueInDays, ?Booking $booking = null): Invoice
    {
        if ($booking && $type === 'Commission') {
            $booking->update(['commission_amount' => round($amount, 2)]);
        }

        return Invoice::create([
            'host_id' => $host->user_id,
            'booking_id' => $booking?->booking_id,
            'invoice_type' => $type,
            'amount' => round($amount, 2),
            'status' => $status,
            'due_date' => today()->addDays($dueInDays),
            'proof_reference' => $status === 'For Verification' ? 'invoice-proofs/sample.png' : null,
            'verified_by' => $status === 'Paid' ? User::where('role', 'Admin')->value('user_id') : null,
            'verified_at' => $status === 'Paid' ? now() : null,
            'paypal_transaction_id' => in_array($status, ['For Verification', 'Paid']) ? strtoupper(Str::random(17)) : null,
        ]);
    }

    // creates a booking (and holds its dates, unless it is cancelled)
    private function book(Unit $unit, User $guest, int $startInDays, int $nights, string $status, ?string $passcode = null): Booking
    {
        $unit->load('rates');
        $checkIn = now()->addDays($startInDays);
        $checkOut = $checkIn->copy()->addDays($nights);

        $booking = Booking::create([
            'booking_reference' => 'BTG-'.strtoupper(Str::random(8)),
            'guest_id' => $guest->user_id, 'unit_id' => $unit->unit_id,
            'check_in_date' => $checkIn->toDateString(), 'check_out_date' => $checkOut->toDateString(),
            'guest_count' => 2, 'booking_status' => $status,
            'total_amount' => $unit->rateFor(2) * $nights, 'door_passcode' => $passcode,
            'authorized_name' => $guest->full_name, 'authorized_contact' => '0917 000 0000', 'authorized_email' => $guest->email,
            'requested_at' => $checkIn->copy()->subDays(10),
        ]);

        if ($status !== 'Cancelled') {
            $unit->availability()->create([
                'start_date' => $checkIn->toDateString(), 'end_date' => $checkOut->copy()->subDay()->toDateString(),
                'availability_status' => 'Booked', 'notes' => $booking->booking_reference,
            ]);
        }

        return $booking;
    }
}