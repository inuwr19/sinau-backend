<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsHistory extends Model
{
    protected $table = 'points_history';

    protected $fillable = [
        'member_id',
        'order_id',
        'points_change',
        'reason',
    ];

    protected $casts = [
        'points_change' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
