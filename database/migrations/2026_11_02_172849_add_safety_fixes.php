<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // new fee settings (the admin can change them in Fee Settings)
        DB::table('fee_settings')->insert([
            ['setting_key' => 'booking_hold_hours', 'setting_value' => '24'],   // #1 time to upload a payment proof
            ['setting_key' => 'stale_paid_hours', 'setting_value' => '48'],     // #4 time before "paid but not confirmed" is flagged
        ]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable();                            // #4
            $table->string('payment_reference', 40)->nullable()->unique();      // #5 receipt number
            $table->string('payment_proof_hash', 64)->nullable()->index();      // #5 fingerprint of the screenshot
        });

        Schema::table('units', function (Blueprint $table) {                    // #6 proof for THIS unit
            $table->string('title_document', 500)->nullable();
            $table->string('permission_document', 500)->nullable();             // condo / HOA permission
        });

        Schema::table('users', function (Blueprint $table) {                    // #10
            $table->dateTime('privacy_consent_at')->nullable();
        });

        // #9 who did what
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id');
            $table->string('action', 100);
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('details', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('privacy_consent_at'));
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn(['title_document', 'permission_document']));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['paid_at', 'payment_reference', 'payment_proof_hash']));
    }
};