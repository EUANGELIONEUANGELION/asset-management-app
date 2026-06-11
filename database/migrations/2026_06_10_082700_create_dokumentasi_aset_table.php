<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi_aset', function (Blueprint $table) {
            $table->id();

            // ── FK ke tabel yang SUDAH PASTI ADA ────────────────────────────
            $table->foreignId('aset_id')
                  ->constrained('aset')
                  ->cascadeOnDelete();

            $table->foreignId('assignment_id')
                  ->nullable()
                  ->constrained('assignments')
                  ->nullOnDelete();

            $table->foreignId('created_by')
                  ->constrained('users');

            // ── Kolom referensi opsional (tanpa FK dulu) ─────────────────────
            // Ditambah FK nanti setelah tabel approval / perpindahan / dst dibuat
            $table->unsignedBigInteger('approval_id')->nullable();
            $table->unsignedBigInteger('perpindahan_id')->nullable();
            $table->unsignedBigInteger('preventif_id')->nullable();
            $table->unsignedBigInteger('peminjaman_id')->nullable();

            // ── Kolom data ───────────────────────────────────────────────────
            $table->enum('jenis_dokumentasi', [
                'input_aset',
                'approval',
                'perpindahan',
                'preventif',
                'peminjaman',
            ])->default('input_aset');

            $table->enum('kondisi', [
                'baru',
                'baik',
                'rusak_ringan',
                'rusak_berat',
            ])->nullable();

            $table->string('keterangan', 500)->nullable();
            $table->string('url_foto');

            $table->timestamps();

            // ── Index ────────────────────────────────────────────────────────
            $table->index(['aset_id', 'jenis_dokumentasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_aset');
    }
};