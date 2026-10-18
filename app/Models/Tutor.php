<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'specialties',
        'hourly_rate',
        'rating',
        'photo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'hourly_rate' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slots()
    {
        return $this->hasMany(TutorSlot::class);
    }

    public function openSlots()
    {
        return $this->slots()->where('status', 'open')->where('starts_at', '>', now());
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
