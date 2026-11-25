<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $guarded = ['id'];

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'event_tenant')
            ->withPivot('quota')
            ->withTimestamps();
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function assignedOfficers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')->withTimestamps();
    }

    public function getFloorPlanUrl(): ?string
    {
        return filled($this->floor_plan_image)
            ? asset('storage/'.ltrim($this->floor_plan_image, '/'))
            : null;
    }
}
