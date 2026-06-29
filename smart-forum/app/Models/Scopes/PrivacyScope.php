<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PrivacyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = auth()->user();

        // If no user (guest) – only show non-private posts
        if (!$user) {
            $builder->where('is_private', false);
            return;
        }

        // Admins and lecturers see everything
        if ($user->isAdmin() || $user->isLecturer()) {
            return;
        }

        // For students:
        $builder->where(function ($query) use ($user) {
            $query->where('is_private', false) // Public posts
                  ->orWhere('user_id', $user->id) // Own posts
                  ->orWhereHas('exclusions', function ($q) use ($user) {
                      // Posts where the user is NOT excluded
                      $q->where('excluded_user_id', '!=', $user->id);
                  });
        });
    }
}