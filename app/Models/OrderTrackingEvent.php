<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTrackingEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'status',
        'notes',
        'carrier',
        'tracking_number',
        'updated_by_type',
        'updated_by_id',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
