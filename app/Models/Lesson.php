<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'module_id',
    'title',
    'slug',
    'content',
    'video_provider',
    'video_id',
    'duration_seconds',
    'position',
    'is_free_preview',
])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'position' => 0,
        'is_free_preview' => false,
    ];

    /**
     * Le slug n'est pas saisi dans l'admin : le suffixe aléatoire garantit
     * l'unicité entre deux leçons de même titre.
     */
    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson): void {
            $lesson->slug ??= Str::slug($lesson->title).'-'.Str::lower(Str::random(5));
        });
    }

    protected function casts(): array
    {
        return [
            'is_free_preview' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Durée au format minutes:secondes, avec les heures au-delà d'une heure
     * (gmdate('i:s') seul afficherait 3 700 s comme « 01:40 »).
     */
    public function durationFormatted(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        return gmdate($this->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $this->duration_seconds);
    }

    public function embedUrl(): ?string
    {
        if ($this->video_id === null) {
            return null;
        }

        return match ($this->video_provider) {
            'youtube' => 'https://www.youtube-nocookie.com/embed/'.$this->video_id,
            'vimeo' => 'https://player.vimeo.com/video/'.$this->video_id,
            default => null,
        };
    }
}
