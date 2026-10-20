<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'test_id',
        'qtype',
        'question_text',
        'passage_text',
        'options',
        'correct_options',
        'explanation',
        'marks',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_options' => 'array',
        ];
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function attemptAnswers()
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
