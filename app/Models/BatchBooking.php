<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchBooking extends Model
{
    protected $fillable = [
        'user_id',
        'batch_id',
        'purchase_id',
        'status',
        'calendar_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'calendar_sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
