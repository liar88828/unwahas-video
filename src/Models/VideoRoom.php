<?php

declare(strict_types=1);

namespace Unwahas\Video\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $user_id
 * @property bool $is_started
 * @property Carbon|null $started_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'user_id', 'is_started', 'started_at'])]
class VideoRoom extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'video_rooms';

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'is_started',
        'started_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_started' => 'boolean',
            'started_at' => 'datetime',
        ];
    }

    /**
     * Start the meeting room.
     */
    public function startRoom(): void
    {
        $this->update([
            'is_started' => true,
            'started_at' => now(),
        ]);
    }

    /**
     * End the meeting room.
     */
    public function endRoom(): void
    {
        $this->update([
            'is_started' => false,
        ]);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        $userModel = config('video.user_model', config('auth.providers.users.model', 'App\\Models\\User'));

        return $this->belongsTo($userModel);
    }

    /**
     * @return HasOne
     */
    public function exam(): HasOne
    {
        $examModel = config('video.exam_model', 'App\\Models\\Exam');

        return $this->hasOne($examModel);
    }
}
