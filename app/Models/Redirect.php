<?php

namespace App\Models;

use App\Observers\RedirectObserver;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'from_path',
    'to_path',
    'status_code',
    'hit_count',
    'last_hit_at',
])]
#[ObservedBy([RedirectObserver::class])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status_code' => 301,
        'hit_count' => 0,
    ];

    /**
     * Ramène toute saisie (« ancien », « /ancien/ », URL complète) à la forme
     * comparée par HandleRedirects : un chemin à une seule barre initiale.
     */
    public static function normalizePath(string $value): string
    {
        return '/'.trim(parse_url(trim($value), PHP_URL_PATH) ?? '', '/');
    }

    /**
     * @return Attribute<string, string>
     */
    protected function fromPath(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizePath($value));
    }

    protected function casts(): array
    {
        return [
            'last_hit_at' => 'datetime',
            'status_code' => 'integer',
            'hit_count' => 'integer',
        ];
    }
}
