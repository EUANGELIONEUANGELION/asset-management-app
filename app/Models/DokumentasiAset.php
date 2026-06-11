<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DokumentasiAset extends Model
{
    protected $table = 'dokumentasi_aset';

    protected $fillable = [
        'aset_id',
        'assignment_id',
        'approval_id',
        'perpindahan_id',
        'preventif_id',
        'peminjaman_id',
        'jenis_dokumentasi',
        'kondisi',
        'keterangan',
        'url_foto',
        'created_by',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function perpindahan(): BelongsTo
    {
        return $this->belongsTo(PerpindahanAset::class, 'perpindahan_id');
    }

    public function preventif(): BelongsTo
    {
        return $this->belongsTo(PreventifAset::class, 'preventif_id');
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    // ── Accessor: URL publik foto ────────────────────────────────────────────

    /**
     * Kembalikan URL publik foto (dari disk "public").
     * Contoh: /storage/dokumentasi/12/foto.jpg
     */
    public function getFotoUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->url_foto);
    }

    // ── Label kondisi (untuk tampilan) ───────────────────────────────────────

    public function getKondisiLabelAttribute(): string
    {
        return match ($this->kondisi) {
            'baru'         => 'Baru',
            'baik'         => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat'  => 'Rusak Berat',
            default        => '-',
        };
    }
}