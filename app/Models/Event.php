<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        if (blank($this->floor_plan_image)) {
            return null;
        }

        $path = ltrim($this->floor_plan_image, '/');

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return '/storage/'.$path;
    }
}
