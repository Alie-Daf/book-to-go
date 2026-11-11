<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status = 'Active'): User
    {
        return User::create([
            'first_name' => $role, 'last_name' => uniqid(), 'email' => uniqid().'@test.test',
            'password_hash' => 'x', 'role' => $role, 'account_status' => $status,
        ]);
    }

    // a host with one unit and one Paid booking: 2 nights x 1,000 = 2,000
    private function paidBooking(string $hostStatus = 'Active'): Booking
    {
        $host = $this->makeUser('Host', $hostStatus);
        $guest = $this->makeUser('Guest');

        $unit = Unit::create([
            'host_id' => $host->user_id, 'title' => 'Test Unit', 'category' => 'Studio', 'address_line' => 'A', 'city' => 'B',
            'province' => 'C', 'max_guests' => 2, 'check_in_time' => '14:00', 'check_out_time' => '12:00',
            'house_rules' => 'None', 'listing_status' => 'Approved',
        ]);

        return Booking::create([
            'booking_reference' => 'BTG-'.uniqid(), 'guest_id' => $guest->user_id, 'unit_id' => $unit->unit_id,
            'check_in_date' => today()->addDays(5)->toDateString(), 'check_out_date' => today()->addDays(7)->toDateString(),
            'guest_count' => 2, 'booking_status' => 'Paid', 'total_amount' => 2000,
            'authorized_name' => 'G', 'authorized_contact' => '1', 'authorized_email' => 'g@test.test', 'requested_at' => now(),
        ]);
    }

    public function test_confirming_creates_one_invoice_of_12_percent(): void
    {
        $booking = $this->paidBooking();

        $invoice = BillingService::confirmBooking($booking);

        $this->assertEquals(240.00, $invoice->amount);                 // 12% of 2,000
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertSame(1, Invoice::count());
        $this->assertSame('Confirmed', $booking->fresh()->booking_status);
    }

    public function test_second_confirm_is_refused_and_creates_no_second_invoice(): void
    {
        $booking = $this->paidBooking();
        BillingService::confirmBooking($booking);

        try {
            BillingService::confirmBooking($booking->fresh());
            $this->fail('The second confirm should be refused.');
        } catch (HttpException $e) {
            $this->assertSame(1, Invoice::count());
        }
    }

    public function test_host_on_hold_cannot_confirm(): void
    {
        $booking = $this->paidBooking('Hold');

        $this->expectException(HttpException::class);
        BillingService::confirmBooking($booking);
    }

    public function test_marking_the_overdue_invoice_paid_lifts_the_hold(): void
    {
        $host = $this->paidBooking()->unit->host;
        $admin = $this->makeUser('Admin');
        $invoice = Invoice::create([
            'host_id' => $host->user_id, 'invoice_type' => 'Commission', 'amount' => 240,
            'status' => 'Unpaid', 'due_date' => today()->subDay()->toDateString(),
        ]);

        BillingService::checkOverdue();
        $this->assertSame('Hold', $host->fresh()->account_status);        // overdue puts the host on hold

        $invoice->update(['status' => 'For Verification']);
        BillingService::markPaid($invoice->fresh(), $admin->user_id);
        $this->assertSame('Active', $host->fresh()->account_status);      // paying lifts it
    }

    public function test_old_pending_booking_expires_and_frees_its_dates(): void
    {
        $booking = $this->paidBooking();
        $booking->update(['booking_status' => 'Pending', 'requested_at' => now()->subHours(30)]);
        $booking->unit->availability()->create([
            'start_date' => today()->addDays(5)->toDateString(), 'end_date' => today()->addDays(6)->toDateString(),
            'availability_status' => 'Booked', 'notes' => $booking->booking_reference,
        ]);

        BillingService::checkOverdue();

        $this->assertSame('Cancelled', $booking->fresh()->booking_status);
        $this->assertSame(0, $booking->unit->availability()->count());
    }

    public function test_unverified_host_is_sent_to_the_profile_page(): void
    {
        $host = $this->paidBooking()->unit->host;

        $this->actingAs($host)->get(route('host.units.index'))->assertRedirect(route('host.profile'));
    }
}