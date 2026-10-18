<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'subject_id',
        'module_id',
        'chapter',
        'title',
        'file_path',
        'visibility',
        'sort_order',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function isEnrolledOnly(): bool
    {
        return $this->visibility === 'enrolled';
    }
}
