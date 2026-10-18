<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteRoster extends Model
{
    protected $table = 'institute_roster';

    protected $fillable = [
        'enrollment_id',
        'student_name',
        'program',
        'batch',
        'is_claimed',
        'claimed_by',
    ];

    protected function casts(): array
    {
        return [
            'is_claimed' => 'boolean',
        ];
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
