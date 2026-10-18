<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
