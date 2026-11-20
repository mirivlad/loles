<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAttachment extends Model
{
    protected $fillable = [
        'course_id', 'module_id', 'uploaded_by', 'draft_token',
        'storage_path', 'original_name', 'mime_type', 'size_bytes',
    ];

    protected $casts = ['size_bytes' => 'integer'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }
}
