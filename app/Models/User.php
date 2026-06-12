<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Properti yang bisa diisi secara massal.
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_telepon',
        'role',
    ];

    /**
     * Properti yang disembunyikan saat serialisasi (misal ke JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data otomatis oleh Laravel 12.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Menjamin password otomatis di-bcrypt saat disimpan
        ];
    }
    public function assignments()
{
    return $this->hasMany(Assignment::class, 'assigned_to');
}
}
