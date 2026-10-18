<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected',
        'is_correct',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'selected' => 'array',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt()
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
