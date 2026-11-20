<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'tenant']);
    }

    public function view(User $user, Guest $guest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Keamanan ekstra: pastikan tenant hanya bisa buka detail tamunya sendiri
        return $user->role === 'tenant' && $user->tenant->id === $guest->tenant_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'tenant']);
    }

    public function update(User $user, Guest $guest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Tenant hanya bisa edit miliknya sendiri DAN jika event masih aktif
        return $user->role === 'tenant'
            && $user->tenant->id === $guest->tenant_id
            && $guest->event->is_active;
    }

    public function delete(User $user, Guest $guest): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Tenant hanya bisa hapus miliknya sendiri DAN jika event masih aktif
        return $user->role === 'tenant'
            && $user->tenant->id === $guest->tenant_id
            && $guest->event->is_active;
    }

    public function deleteAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'tenant']);
    }
}
