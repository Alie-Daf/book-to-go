<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // login uses first name + last name, so the pair must identify one person
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['first_name', 'last_name'], 'users_full_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropUnique('users_full_name_unique'));
    }
};