<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorSlot extends Model
{
    protected $fillable = ['tutor_id', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function session()
    {
        return $this->hasOne(Session::class, 'slot_id');
    }
}
