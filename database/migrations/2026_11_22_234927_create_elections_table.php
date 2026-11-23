<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// SPEC §4.2 — Pemilihan
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('school_year', 9);
            $table->string('status', 20)->default('preparation');
            $table->boolean('is_active')->default(false);
            $table->string('touch_mode', 10)->default('confirm');
            $table->unsignedTinyInteger('reset_delay_seconds')->default(3);
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('confetti_enabled')->default(true);
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        // Hanya satu pemilihan boleh aktif (REQ-02) — dijaga juga di level database.
        DB::statement('CREATE UNIQUE INDEX elections_single_active ON elections (is_active) WHERE is_active = 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
