<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $table = 'batches';

    protected $fillable = [
        'title',
        'description',
        'subject_id',
        'session_time',
        'mode',
        'location',
        'starts_at',
        'ends_at',
        'max_seats',
        'challan_info',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function bookings()
    {
        return $this->hasMany(BatchBooking::class);
    }
}
