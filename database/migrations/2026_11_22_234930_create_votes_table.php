<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §4.5 — Suara.
// SENGAJA tanpa kolom identitas siswa, IP, user-agent, token, dan updated_at (REQ-18, REQ-19).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('polling_station_id')->constrained()->restrictOnDelete();
            $table->boolean('is_trial')->default(false);
            $table->uuid('client_uuid')->unique(); // kunci idempoten (REQ-11)
            $table->dateTime('created_at');

            $table->index(['election_id', 'is_trial', 'candidate_id']); // rekap
            $table->index(['polling_station_id', 'created_at']);         // rem 2 detik (REQ-21)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
