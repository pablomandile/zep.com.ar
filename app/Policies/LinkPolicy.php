<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    public function view(User $user, Link $link): bool
    {
        return $user->is_admin || $link->user_id === $user->id;
    }

    public function update(User $user, Link $link): bool
    {
        return $user->is_admin || $link->user_id === $user->id;
    }

    public function delete(User $user, Link $link): bool
    {
        return $user->is_admin || $link->user_id === $user->id;
    }
}
