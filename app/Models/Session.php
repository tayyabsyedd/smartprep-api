<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * 1-on-1 tutoring sessions. Table is named tutoring_sessions because
     * Laravel reserves `sessions` for its database session driver.
     */
    protected $table = 'tutoring_sessions';

    protected $fillable = [
        'student_id',
        'tutor_id',
        'slot_id',
        'purchase_id',
        'meeting_url',
        'meeting_provider',
        'status',
        'reminder_24h_sent_at',
        'reminder_1h_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_24h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function slot()
    {
        return $this->belongsTo(TutorSlot::class, 'slot_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
