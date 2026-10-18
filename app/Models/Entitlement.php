<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $fillable = [
        'user_id',
        'entitled_type',
        'entitled_id',
        'source',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entitled()
    {
        return $this->morphTo();
    }

    public function isLive(): bool
    {
        return $this->revoked_at === null;
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => now()]);
    }
}
