<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'points',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pointsHistory(): HasMany
    {
        return $this->hasMany(PointsHistory::class);
    }
}
