<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'password', 'role', 'tenant_id', 'is_active',
        'preferred_scan_mode', 'preferred_event_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Hanya admin dan tenant yang aktif yang boleh login ke dashboard Filament (/)
        return (bool) $this->is_active && in_array($this->role, ['admin', 'tenant'], true);
    }

    // public function tenant(): HasOne
    // {
    //     return $this->hasOne(Tenant::class);
    // }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function preferredEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'preferred_event_id');
    }

    public function attendancesScanned(): HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }
}
