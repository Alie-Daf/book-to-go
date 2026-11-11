<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- fee settings: every fee is read from here, nothing is hardcoded ----------
        Schema::create('fee_settings', function (Blueprint $table) {
            $table->string('setting_key', 60)->primary();
            $table->string('setting_value', 100);
            $table->foreignId('updated_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('fee_settings')->insert([
            ['setting_key' => 'commission_rate', 'setting_value' => '0.12'],
            ['setting_key' => 'free_image_limit', 'setting_value' => '7'],
            ['setting_key' => 'invoice_due_days', 'setting_value' => '3'],
            ['setting_key' => 'max_featured_per_page', 'setting_value' => '3'],
            ['setting_key' => 'featured_price_week', 'setting_value' => '199'],
            ['setting_key' => 'featured_price_month', 'setting_value' => '599'],
            ['setting_key' => 'free_unit_limit', 'setting_value' => '10'],
            ['setting_key' => 'unit_slot_price', 'setting_value' => '99'],
            ['setting_key' => 'platform_gcash_name', 'setting_value' => 'Book To Go Platform'],
            ['setting_key' => 'platform_gcash_number', 'setting_value' => '0917 000 0000'],
        ]);

        // ---------- invoices: what hosts owe the platform ----------
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->foreignId('host_id')->constrained('users', 'user_id');
            $table->foreignId('booking_id')->nullable()->constrained('bookings', 'booking_id');
            $table->enum('invoice_type', ['Commission', 'Image Pack', 'Unit Slot', 'Featured']);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['Unpaid', 'For Verification', 'Paid', 'Overdue', 'Cancelled'])->default('Unpaid');
            $table->date('due_date');
            $table->string('proof_reference', 500)->nullable();
            $table->string('admin_note', 500)->nullable();          // why the admin rejected the proof
            $table->foreignId('verified_by')->nullable()->constrained('users', 'user_id');
            $table->dateTime('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['booking_id', 'invoice_type']);         // a booking can have only one commission invoice
            $table->index(['status', 'due_date']);
        });

        // ---------- image packs ----------
        Schema::create('image_packs', function (Blueprint $table) {
            $table->id('pack_id');
            $table->string('pack_name', 60)->unique();
            $table->unsignedSmallInteger('max_images');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('duration_days');
        });

        DB::table('image_packs')->insert([
        ['pack_name' => 'Plus', 'max_images' => 10, 'price' => 49, 'duration_days' => 30],
        ['pack_name' => 'Pro', 'max_images' => 20, 'price' => 89, 'duration_days' => 30],
        ]);

        Schema::create('unit_image_packs', function (Blueprint $table) {
            $table->id('unit_pack_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->foreignId('pack_id')->constrained('image_packs', 'pack_id');
            $table->foreignId('invoice_id')->constrained('invoices', 'invoice_id');
            $table->date('start_date')->nullable();                 // filled when the invoice is Paid
            $table->date('end_date')->nullable();
        });

        // ---------- featured listings ----------
        Schema::create('featured_listings', function (Blueprint $table) {
            $table->id('featured_id');
            $table->foreignId('unit_id')->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices', 'invoice_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('featured_status', ['Pending', 'Active', 'Expired', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'user_id');
        });

        // ---------- changes to existing tables ----------
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('commission_amount', 10, 2)->nullable();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_status', ['Pending', 'Paid', 'Confirmed', 'Cancelled'])->default('Pending')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['Active', 'Hold', 'Suspended', 'Removed'])->default('Active')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('extra_unit_slots')->default(0);   // bought unit slots
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('extra_unit_slots'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('commission_amount'));

        foreach (['featured_listings', 'unit_image_packs', 'image_packs', 'invoices', 'fee_settings'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};