<?php

namespace App\Traits;

use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;

trait BypassesBranchScopeTrait
{
    /**
     * Call this to remove branch scope for queries:
     * Model::withoutBranchScope()->get();
     */
    public static function withoutBranchScope()
    {
        return (new static)->newQueryWithoutScope(new BranchScope);
    }
}
