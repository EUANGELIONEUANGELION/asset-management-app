<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Kamus Master Saran Poin Tugas (Untuk Autocomplete/Saran Supervisor)
        Schema::create('master_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('nama_poin')->unique(); // Contoh: "Cek RAM Perangkat", "Pastikan Serial Number SAP Sesuai"
            $table->timestamps();
        });

        // 2. Transaksi Poin Tugas per Penugasan (Menyimpan status centang Tim)
        Schema::create('assignment_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->string('nama_poin');
            $table->boolean('is_checked')->default(false); // Status centang oleh Tim Lapangan
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_checkpoints');
        Schema::dropIfExists('master_checkpoints');
    }
};