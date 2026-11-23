<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC §4.3 — Paslon
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('chair_name', 60);
            $table->string('chair_class', 20)->nullable();
            $table->string('vice_name', 60);
            $table->string('vice_class', 20)->nullable();
            $table->string('tagline', 100);
            $table->text('vision');
            $table->json('missions');
            $table->string('photo_path');
            $table->string('accent', 10);
            $table->timestamps();

            $table->unique(['election_id', 'number']);
            $table->unique(['election_id', 'accent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
