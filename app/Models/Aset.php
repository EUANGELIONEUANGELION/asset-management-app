<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit jika tidak jamak (plural)
    protected $table = 'aset'; 

    protected $fillable = [
        'no_aset',
        'rfid_code',
        'no_sap',
        'jenis_aset_id',
        'ruang_id',
        'lokasi_id',
        'pengguna_id',
        'status',
        'created_by',
    ];

    /**
     * Relasi Balik ke Assignment
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'aset_id');
    }
}
