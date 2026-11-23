<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SPEC v1.1 §4.2 — pengaturan berita acara (keputusan pemilik CP5).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->string('school_name', 150)->nullable();
            $table->string('school_address', 255)->nullable();
            $table->string('signing_place', 60)->nullable();
            $table->json('signatories')->nullable();
            $table->boolean('witness_signatures')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'school_address', 'signing_place', 'signatories', 'witness_signatures']);
        });
    }
};
