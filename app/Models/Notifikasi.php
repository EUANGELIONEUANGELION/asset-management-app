<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit sesuai ERD Anda
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'aset_id',
        'pesan',
        'channel', // 'whatsapp', 'system', dll.
        'is_read',
    ];

    /**
     * Atribut yang harus di-cast secara otomatis.
     */
    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relasi ke data User (Membaca siapa yang menerima notifikasi ini)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke data Aset (Membaca aset mana yang memicu notifikasi ini)
     */
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }
}