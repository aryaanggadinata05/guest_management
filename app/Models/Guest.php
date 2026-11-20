<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_attended' => 'boolean',
        'wa_sent_at' => 'datetime',
        'adult_pax' => 'integer',
        'child_pax' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        // Otomatis generate QR Code (UUID) saat data guest di-create
        static::creating(function ($model) {
            if (empty($model->qr_code)) {
                $model->qr_code = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
