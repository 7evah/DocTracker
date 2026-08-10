<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

/**
 * An immutable uploaded revision (§6, §22).
 *
 * Nothing in the application should ever update file_path, revision or
 * uploaded_by after creation.
 */
class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'revision',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'version_notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    /** Human-readable size for the revision table. */
    public function formattedSize(): string
    {
        return Number::fileSize($this->file_size ?? 0, precision: 1);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
