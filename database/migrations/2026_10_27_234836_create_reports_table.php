<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('reporter_id')->constrained('users', 'user_id');
            $table->foreignId('review_id')->nullable()->constrained('reviews', 'review_id')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units', 'unit_id')->cascadeOnDelete();
            $table->text('reason');
            $table->enum('status', ['Open', 'Resolved'])->default('Open');
            $table->string('resolution_notes', 1000)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users', 'user_id');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};