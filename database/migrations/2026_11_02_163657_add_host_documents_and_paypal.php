<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. more document types for host verification
        Schema::table('user_documents', function (Blueprint $table) {
            $table->enum('document_type', ['Valid ID', 'Selfie with ID', 'Business Permit', 'Property Title'])->change();
        });

        // 2. platform fees are paid through PayPal instead of GCash
        DB::table('fee_settings')->where('setting_key', 'platform_gcash_name')
            ->update(['setting_key' => 'platform_paypal_name']);
        DB::table('fee_settings')->where('setting_key', 'platform_gcash_number')
            ->update(['setting_key' => 'platform_paypal_email', 'setting_value' => 'payments@booktogo.example']);

        // 3. the host types the PayPal Transaction ID with his proof (one payment cannot be used twice)
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('paypal_transaction_id', 30)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('paypal_transaction_id'));
    }
};