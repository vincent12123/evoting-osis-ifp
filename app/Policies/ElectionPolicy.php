<?php

namespace App\Policies;

use App\Models\Election;
use App\Models\User;

/** Pemilihan tidak dihapus — pemilihan lama cukup diarsipkan (SPEC §3.2). */
class ElectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Election $election): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Election $election): bool
    {
        return true;
    }

    public function delete(User $user, Election $election): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
