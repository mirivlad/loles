<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePackage extends Model
{
    protected $fillable = [
        'course_id', 'uploaded_by', 'format', 'standard_version', 'title',
        'original_filename', 'storage_path', 'sha256', 'size_bytes', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
