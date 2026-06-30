<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App-owned news/announcement post (public.news). Unlike the OpenMU models,
 * this uses normal Laravel timestamps.
 */
class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'slug', 'title_vi', 'title_en', 'body_vi', 'body_en',
        'excerpt_vi', 'excerpt_en', 'cover_image',
        'is_published', 'published_at', 'author_account_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    /** Localized title for the current (or given) locale, falling back to Vietnamese. */
    public function title(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return ($locale === 'en' && $this->title_en) ? $this->title_en : $this->title_vi;
    }

    /** Localized body for the current (or given) locale, falling back to Vietnamese. */
    public function body(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return ($locale === 'en' && $this->body_en) ? $this->body_en : $this->body_vi;
    }

    /** Localized excerpt; derives from body if not set. */
    public function excerpt(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $value = ($locale === 'en' && $this->excerpt_en) ? $this->excerpt_en : $this->excerpt_vi;
        return $value ?: Str::limit(strip_tags($this->body($locale)), 160);
    }

    /** Generate a unique URL slug from a title. */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
