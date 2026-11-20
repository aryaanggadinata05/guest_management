<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'capacity',
        'pos_x',
        'pos_y',
        'zone',
        'pic_name',
    ];

    protected $casts = [
        'pos_x' => 'float',
        'pos_y' => 'float',
        'capacity' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    // Menghitung jumlah kursi terisi (memperhitungkan kehadiran aktual atau rencana pax)
    public function getOccupiedSeats(): int
    {
        $occupied = 0;
        foreach ($this->guests as $guest) {
            if ($guest->is_attended && $guest->attendance) {
                $occupied += ((int) $guest->attendance->adult_count + (int) $guest->attendance->child_count);
            } else {
                $occupied += ((int) $guest->adult_pax + (int) $guest->child_pax);
            }
        }

        return $occupied;
    }

    // Menghitung sisa kursi meja
    public function getRemainingSeats(): int
    {
        return $this->capacity - $this->getOccupiedSeats();
    }

    // Menentukan kode warna status meja: Hijau (>2), Kuning/Amber (1-2), Merah (<=0)
    public function getStatusColor(): string
    {
        $remaining = $this->getRemainingSeats();
        if ($remaining > 2) {
            return '#10b981';
        }
        if ($remaining > 0) {
            return '#f59e0b';
        }

        return '#ef4444';
    }

    // Helper untuk mengecek sisa kursi di meja ini (backward compatibility)
    public function getRemainingCapacityAttribute()
    {
        return $this->getRemainingSeats();
    }
}
