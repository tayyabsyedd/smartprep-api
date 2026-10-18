<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'kind',
        'timed',
        'duration_minutes',
        'is_free',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'timed' => 'boolean',
            'is_free' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_test')->withTimestamps();
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function entitlements()
    {
        return $this->morphMany(Entitlement::class, 'entitled');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
