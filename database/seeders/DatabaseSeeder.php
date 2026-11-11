<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\HostVerification;
use App\Models\Message;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    // Unsplash photo ids used as sample pictures of the units
    private array $photos = [
        'photo-1522708323590-d24dbb6b0267', 'photo-1502672260266-1c1ef2d93688', 'photo-1505691938895-1758d7feb511',
        'photo-1493809842364-78817add7ffb', 'photo-1522771739844-6a9f6d5f14af', 'photo-1560448204-e02f11c3d0e2',
        'photo-1484154218962-a197022b5858', 'photo-1512918728675-ed5a9ecdebfd', 'photo-1564013799919-ab600027ffc6',
        'photo-1568605114967-8130f3a36994', 'photo-1545324418-cc1a3fa10c00', 'photo-1631049307264-da0ec9d70304',
    ];

    public function run(): void
    {
        // ---------- users ----------
        $admin = $this->makeUser('Admin User', 'admin@booktogo.test', 'Admin');
        $maria = $this->makeUser('Maria Santos', 'maria@booktogo.test', 'Host', 'PayPal 0917 123 4567 (Maria Santos)');
        $jose = $this->makeUser('Jose Reyes', 'jose@booktogo.test', 'Host', 'PayPal 0918 765 4321 (Jose Reyes)');
        $juan = $this->makeUser('Juan Dela Cruz', 'guest@booktogo.test', 'Guest');
        $pedro = $this->makeUser('Pedro Ramos', 'pedro@booktogo.test', 'Guest');
        $lia = $this->makeUser('Lia Mendoza', 'lia@booktogo.test', 'Guest');

        HostVerification::create(['host_id' => $maria->user_id, 'reviewed_by' => $admin->user_id, 'status' => 'Verified', 'reviewed_at' => now()]);
        HostVerification::create(['host_id' => $jose->user_id]);

        foreach ([$juan, $pedro, $lia] as $guest) {
            UserDocument::create(['user_id' => $guest->user_id, 'document_type' => 'Valid ID', 'file_reference' => 'valid-ids/sample.png', 'verification_status' => 'Approved']);
        }

        // ---------- amenities ----------
        $all = ['Wi-Fi', 'A/C', 'Kitchen', 'Parking', 'Pool'];
        $amenityIds = collect($all)->mapWithKeys(fn ($name) => [$name => Amenity::create(['amenity_name' => $name])->amenity_id]);

        // ---------- units ----------
        // title, category, host, address, city, province, max guests, amenities, rates [guests => price], photo number
        $rows = [
            ['Cozy Studio', 'Studio', $maria, 'Trees Residences, Novaliches', 'Quezon City', 'Metro Manila', 2, ['Wi-Fi', 'A/C', 'Kitchen'], [2 => 1000], 0],
            ['Modern Condo Unit', 'Condo', $maria, 'Trees Residences, Novaliches', 'Quezon City', 'Metro Manila', 4, $all, [2 => 1800, 4 => 2400], 10],
            ['Spacious House', 'House', $jose, 'Balibago', 'Angeles City', 'Pampanga', 8, $all, [4 => 3500, 8 => 5000], 8],
            ['Family Apartment', 'Apartment', $jose, 'Fairview', 'Quezon City', 'Metro Manila', 5, ['Wi-Fi', 'A/C', 'Kitchen', 'Parking'], [3 => 2200, 5 => 2800], 1],
            ['Budget Room', 'Room', $jose, 'Poblacion', 'Magalang', 'Pampanga', 2, ['Wi-Fi', 'A/C'], [2 => 700], 4],
            ['Skyline Studio', 'Studio', $maria, 'Poblacion', 'Makati City', 'Metro Manila', 2, ['Wi-Fi', 'A/C', 'Kitchen', 'Pool'], [2 => 1600], 5],
            ['Pool View Condo', 'Condo', $maria, 'Tagaytay Ridge', 'Tagaytay City', 'Cavite', 4, $all, [2 => 2600, 4 => 3200], 11],
            ['Garden House', 'House', $jose, 'Aurora Hill', 'Baguio City', 'Benguet', 6, ['Wi-Fi', 'Kitchen', 'Parking'], [4 => 3000, 6 => 3800], 9],
            ['Minimalist Room', 'Room', $maria, 'Cubao', 'Quezon City', 'Metro Manila', 2, ['Wi-Fi', 'A/C'], [2 => 900], 3],
            ['Loft Apartment', 'Apartment', $jose, 'Ortigas Center', 'Pasig City', 'Metro Manila', 3, ['Wi-Fi', 'A/C', 'Kitchen', 'Pool'], [2 => 2000, 3 => 2400], 7],
        ];

        $units = [];
        foreach ($rows as $i => [$title, $category, $host, $address, $city, $province, $maxGuests, $amenities, $rates, $photo]) {
            $unit = Unit::create([
                'host_id' => $host->user_id,
                'title' => $title,
                'category' => $category,
                'description' => "A comfortable {$category} in {$city}, perfect for a relaxing stay. Clean, bright and close to shops and transport.",
                'address_line' => $address,
                'city' => $city,
                'province' => $province,
                'max_guests' => $maxGuests,
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'house_rules' => 'No smoking. No parties. Quiet hours start at 10 PM.',
                'listing_status' => 'Approved',
                'approved_by' => $admin->user_id,
                'approved_at' => now(),
            ]);

            foreach ($rates as $guests => $price) {
                $unit->rates()->create(['guest_count' => $guests, 'nightly_rate' => $price, 'valid_from' => '2026-01-01']);
            }
            $unit->amenities()->attach(collect($amenities)->map(fn ($name) => $amenityIds[$name])->all());
            foreach (range(0, 3) as $n) {
                $unit->images()->create(['image_url' => $this->photoUrl($photo + $n), 'alt_text' => $title, 'display_order' => $n]);
            }

            $units[$i + 1] = $unit;
        }

        // the host closed these dates on Cozy Studio
        $units[1]->availability()->create([
            'start_date' => now()->addDays(40)->toDateString(), 'end_date' => now()->addDays(42)->toDateString(),
            'availability_status' => 'Blocked', 'notes' => 'Host unavailable',
        ]);

        // ---------- bookings ----------
        $this->review($this->book($units[2], $juan, -35, 2, 'Confirmed'), 5, 'Very clean and exactly as described. Maria replied quickly!');
        $this->book($units[1], $juan, -60, 3, 'Confirmed');                 // past stay: Juan can write a review
        $this->book($units[3], $juan, 30, 3, 'Confirmed', '482913');        // upcoming, with door passcode
        $this->book($units[4], $juan, 45, 2, 'Pending');
        $this->book($units[5], $juan, 20, 2, 'Paid');
        $this->book($units[1], $pedro, 10, 4, 'Paid');                       // occupies dates on the calendar
        $this->book($units[2], $pedro, 14, 2, 'Confirmed', '730154');

        // reviews from past stays so the units show ratings
        foreach ([
            [1, $lia, 5, 'Cozy and quiet. Check-in was smooth.'],
            [3, $pedro, 4, 'Plenty of space for the whole family. The pool was great.'],
            [4, $lia, 4, 'Good location and a comfortable apartment.'],
            [6, $pedro, 5, 'Amazing view and a very friendly host.'],
            [7, $lia, 5, 'Perfect weekend getaway.'],
            [10, $pedro, 4, 'Stylish loft. Would stay again.'],
        ] as $i => [$unit, $guest, $rating, $comment]) {
            $this->review($this->book($units[$unit], $guest, -90 - $i * 7, 2, 'Confirmed'), $rating, $comment);
        }

        // ---------- bookmarks and messages ----------
        $juan->bookmarks()->attach([$units[1]->unit_id, $units[2]->unit_id, $units[3]->unit_id]);

        foreach ([
            [$juan, $maria, 'Hi! Is the Modern Condo Unit still open next month?'],
            [$maria, $juan, 'Hello Juan! Yes, you can pick your dates on the unit page.'],
            [$jose, $juan, 'Welcome to Book To Go! Let me know if you have questions about the Spacious House.'],
        ] as [$from, $to, $body]) {
            Message::create(['sender_id' => $from->user_id, 'receiver_id' => $to->user_id, 'body' => $body]);
        }
        $this->call(BillingSeeder::class);
    }

    private function makeUser(string $name, string $email, string $role, ?string $payment = null): User
    {
        [$first, $last] = explode(' ', $name, 2);

        return User::create([
            'first_name' => $first, 'last_name' => $last, 'email' => $email,
            'password_hash' => Hash::make('password'), 'role' => $role, 'payment_instructions' => $payment,
        ]);
    }

    private function photoUrl(int $index): string
    {
        return 'https://images.unsplash.com/'.$this->photos[$index % count($this->photos)].'?auto=format&fit=crop&w=900&q=70';
    }

    // creates a booking and blocks its dates on the calendar
    private function book(Unit $unit, User $guest, int $startInDays, int $nights, string $status, ?string $passcode = null): Booking
    {
        $unit->load('rates');
        $checkIn = now()->addDays($startInDays);
        $checkOut = $checkIn->copy()->addDays($nights);
        $guests = min(2, $unit->max_guests);

        $booking = Booking::create([
            'booking_reference' => 'BTG-'.strtoupper(Str::random(8)),
            'guest_id' => $guest->user_id,
            'unit_id' => $unit->unit_id,
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'guest_count' => $guests,
            'booking_status' => $status,
            'total_amount' => $unit->rateFor($guests) * $nights,
            'door_passcode' => $passcode,
            'authorized_name' => $guest->full_name,
            'authorized_contact' => '0917 000 0000',
            'authorized_email' => $guest->email,
            'requested_at' => $checkIn->copy()->subDays(10),
        ]);

        $unit->availability()->create([
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->copy()->subDay()->toDateString(),
            'availability_status' => 'Booked',
            'notes' => $booking->booking_reference,
        ]);

        return $booking;
    }

    private function review(Booking $booking, int $rating, string $comment): void
    {
        Review::create([
            'booking_id' => $booking->booking_id, 'guest_id' => $booking->guest_id,
            'unit_id' => $booking->unit_id, 'rating' => $rating, 'comment' => $comment,
        ]);
    }
}