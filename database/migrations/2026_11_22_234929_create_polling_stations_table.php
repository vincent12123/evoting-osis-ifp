<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §4.4 — TPS (satu TPS = satu IFP)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('location', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->char('activation_code', 6)->nullable()->unique();
            $table->dateTime('activation_code_expires_at')->nullable();
            $table->string('device_token_hash', 64)->nullable()->unique();
            $table->dateTime('device_activated_at')->nullable();
            $table->dateTime('device_last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_stations');
    }
};
