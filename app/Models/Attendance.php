<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'guest_id',
        'user_id',
        'adult_count',
        'child_count',
        'scanned_at',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    // Relasi ke User (Petugas Scanner)
    public function scanner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
