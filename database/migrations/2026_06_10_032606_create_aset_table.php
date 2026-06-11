<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('aset', function (Blueprint $table) {
        $table->id();
        $table->string('no_aset')->unique();
        $table->string('rfid_code')->nullable()->unique();
        $table->string('no_sap')->nullable();
        $table->foreignId('jenis_aset_id')->nullable();
        $table->foreignId('ruang_id')->nullable();
        $table->foreignId('lokasi_id')->nullable();
        $table->foreignId('pengguna_id')->nullable(); // Ditugaskan ke siapa saat ready
        $table->string('status')->default('pending'); // pending, pending_approval, ready
        $table->foreignId('created_by')->nullable(); // ID Supervisor pembuat tugas
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
