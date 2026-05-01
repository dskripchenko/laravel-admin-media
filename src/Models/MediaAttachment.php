<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot между Media и любой моделью через morph (одна Media в нескольких
 * записях с разными role + position).
 *
 * @property int $id
 * @property int $media_id
 * @property string $attachable_type
 * @property int|string $attachable_id
 * @property string $role
 * @property int $position
 */
final class MediaAttachment extends Model
{
    protected $table = 'admin_media_attachments';

    protected $guarded = ['id'];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
