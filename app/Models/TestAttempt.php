<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'test_id',
        'status',
        'started_at',
        'submitted_at',
        'duration_seconds',
        'time_spent_seconds',
        'score',
        'total_marks',
        'app_switches',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function answers()
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
