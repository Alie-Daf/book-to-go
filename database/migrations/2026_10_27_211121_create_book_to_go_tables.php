<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 254)->unique();
            $table->string('password_hash');
            $table->string('phone_number', 30)->nullable();
            $table->enum('role', ['Admin', 'Host', 'Guest']);
            $table->enum('account_status', ['Active', 'Suspended', 'Removed'])->default('Active');
            $table->text('payment_instructions')->nullable(); // added: host's GCash number/account
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id('unit_id');
            $table->foreignId('host_id')->constrained('users', 'user_id');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->enum('category', ['Room', 'Studio', 'Apartment', 'Condo', 'House']);
            $table->string('address_line');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 20)->nullable();
            $table->unsignedSmallInteger('max_guests');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->text('house_rules');
            $table->enum('listing_status', ['Pending', 'Approved', 'Inactive'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_images', function (Blueprint $table) {
            $table->id('image_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->string('image_url', 1000);
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamp('uploaded_at')->useCurrent();
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id('amenity_id');
            $table->string('amenity_name', 100)->unique();
            $table->string('description', 500)->nullable();
        });

        Schema::create('unit_amenities', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities', 'amenity_id')->cascadeOnDelete();
            $table->primary(['unit_id', 'amenity_id']);
        });

        Schema::create('unit_rates', function (Blueprint $table) {
            $table->id('rate_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('guest_count');
            $table->decimal('nightly_rate', 10, 2);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('unit_availability', function (Blueprint $table) {
            $table->id('availability_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('availability_status', ['Open', 'Booked', 'Blocked']);
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->foreignId('guest_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['guest_id', 'unit_id']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->string('booking_reference', 30)->unique();
            $table->foreignId('guest_id')->constrained('users', 'user_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedSmallInteger('guest_count');
            $table->enum('booking_status', ['Pending', 'Paid', 'Confirmed'])->default('Pending'); // no cancellations
            $table->string('guest_message', 1000)->nullable();
            $table->string('host_response', 1000)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->dateTime('responded_at')->nullable();
            // added columns
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_proof')->nullable();
            $table->string('door_passcode', 10)->nullable();
            $table->string('authorized_name');     // guest authorization form
            $table->string('authorized_contact', 30);
            $table->string('authorized_email');
        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->enum('document_type', ['Valid ID', 'Business Permit']);
            $table->string('file_reference', 500);
            $table->enum('verification_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id');
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->string('reviewer_notes', 1000)->nullable();
        });

        Schema::create('host_verifications', function (Blueprint $table) {
            $table->id('host_verification_id');
            $table->foreignId('host_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'user_id');
            $table->enum('status', ['Pending', 'Verified', 'Rejected'])->default('Pending');
            $table->timestamp('submitted_at')->useCurrent();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('reviewer_notes', 1000)->nullable();
        });

        // added table: reviews and feedback (after the stay)
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->foreignId('booking_id')->unique()->constrained('bookings', 'booking_id');
            $table->foreignId('guest_id')->constrained('users', 'user_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->text('host_reply')->nullable();
            $table->timestamps();
        });

        // added table: host-guest messaging
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('sender_id')->constrained('users', 'user_id');
            $table->foreignId('receiver_id')->constrained('users', 'user_id');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['messages', 'reviews', 'host_verifications', 'user_documents', 'bookings', 'bookmarks',
                  'unit_availability', 'unit_rates', 'unit_amenities', 'amenities', 'unit_images', 'units', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};