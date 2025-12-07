<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * If user is not authenticated or doesn't have branch_id, do nothing.
     * If user has role 'admin', do nothing (admin sees all branches).
     */
    public function apply(Builder $builder, Model $model)
    {
        // Only apply for HTTP/authenticated contexts where a user exists
        $user = null;
        try {
            $user = Auth::user();
        } catch (\Throwable $e) {
            $user = null;
        }

        if (!$user) {
            // no authenticated user -> don't apply (or you may restrict instead)
            return;
        }

        // super-admin bypass
        if (property_exists($user, 'role') && $user->role === 'admin') {
            return;
        }

        if (isset($user->branch_id) && $user->branch_id) {
            // Apply where clause to the model's table.branch_id
            $builder->where($model->getTable() . '.branch_id', $user->branch_id);
        }
    }
}
