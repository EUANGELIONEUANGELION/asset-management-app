<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aset extends Model
{
    // Mengunci nama tabel utama logistik sesuai skema database PT Wismilak
    protected $table = 'aset';

    // Definisikan atribut inti mass-assignable agar sinkron dengan form pengisian
    protected $fillable = [
        'no_aset',
        'no_sap',
        'rfid_code',
        'status',
        'created_by',
    ];

    // ════════════════════════════════════════════════════════════════════════
    // CORE FIX RELATIONSHIP: Menghubungkan Aset ke Tabel Multi-Modul Dokumentasi
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Relasi ke model DokumentasiAset (One-to-Many).
     * Memungkinkan penarikan data log berkas/foto kondisi fisik 
     * tanpa crash meskipun data lampiran foto dari tim lapangan masih kosong.
     */
    public function dokumentasi(): HasMany
    {
        // 'aset_id' sebagai Foreign Key di tabel dokumentasi_aset, 'id' sebagai Local Key di tabel asets
        return $this->hasMany(DokumentasiAset::class, 'aset_id', 'id');
    }

    // ════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS UTALITAS PENUNJANG TRACKING WORKFLOW
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Relasi Balik ke Data Penugasan Kerja Lapangan (One-to-Many).
     * Digunakan untuk memantau riwayat instruksi supervisor yang mengikat unit ini.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'aset_id', 'id');
    }

    /**
     * Relasi ke data Pengguna Pembuat Entitas (BelongsTo).
     * Melacak akun staff/supervisor yang mendaftarkan cikal bakal draf aset pertama kali.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // ════════════════════════════════════════════════════════════════════════
    // UTILITY BUSINESS LOGIC METHODS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Helper Check Kelayakan Cetak Label.
     * Memastikan string RFID / QR identifier dan nomor SAP sudah terbuat secara sah.
     */
    public function isReadyToPrint(): bool
    {
        return $this->status === 'ready' && !empty($this->rfid_code);
    }
}