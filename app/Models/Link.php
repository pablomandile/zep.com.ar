<?php

namespace App\Models;

use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $original_url
 * @property string $url_hash
 * @property string|null $title
 * @property bool $is_custom
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property int $clicks_count
 * @property Carbon|null $last_clicked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $short_url
 */
#[Fillable(['code', 'original_url', 'title', 'is_custom', 'is_active', 'expires_at'])]
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory, SoftDeletes;

    protected $appends = ['short_url'];

    protected static function booted(): void
    {
        static::saving(function (Link $link) {
            if ($link->isDirty('original_url')) {
                $link->url_hash = self::hashUrl($link->original_url);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'clicks_count' => 'integer',
        ];
    }

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LinkClick, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function getShortUrlAttribute(): string
    {
        return self::shortUrlFor($this->code);
    }

    public static function shortUrlFor(string $code): string
    {
        $scheme = app()->isProduction() ? 'https' : parse_url((string) config('app.url'), PHP_URL_SCHEME);

        return ($scheme ?: 'https').'://'.config('shortener.domain').'/'.$code;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Un link redirige si está activo y no venció.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * @param  Builder<Link>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
