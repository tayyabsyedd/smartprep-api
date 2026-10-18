<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $fillable = ['name', 'description', 'price', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, 'bundle_test')->withTimestamps();
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function entitlements()
    {
        return $this->morphMany(Entitlement::class, 'entitled');
    }
}
