<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'aset_id',
        'template_id',
        'assigned_by',
        'assigned_to',
        'jenis_tugas',
        'status',
        'deskripsi',
        'assigned_at',
    ];

    /**
     * HARUS bernama 'aset' (huruf kecil semua)
     */
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    /**
     * HARUS bernama 'receiver' (huruf kecil semua)
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * HARUS bernama 'supervisor' (huruf kecil semua)
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Supervisor pemberi tugas
public function assigner()
{
    return $this->belongsTo(User::class, 'assigned_by');
}
}
