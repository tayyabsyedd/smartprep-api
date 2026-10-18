<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['subject_id', 'name', 'description', 'sort_order'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class)->orderBy('id');
    }

    public function publishedTests()
    {
        return $this->tests()->where('status', 'published');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
