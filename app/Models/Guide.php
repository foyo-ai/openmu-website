<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App-owned player guide / cẩm nang post (public.guides). Bilingual (vi/en);
 * body is trusted HTML authored by GMs. Grouped by `category` on the index.
 */
class Guide extends Model
{
    protected $table = 'guides';

    /** Categories used to group guides on the index and in nav. */
    public const CATEGORIES = ['class', 'gear', 'wings', 'stats', 'general'];

    protected $fillable = [
        'slug', 'category', 'class_key', 'icon',
        'title_vi', 'title_en', 'body_vi', 'body_en',
        'excerpt_vi', 'excerpt_en', 'cover_image',
        'sort_order', 'is_published', 'author_account_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title_vi');
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /** Localized title for the current (or given) locale, falling back to Vietnamese. */
    public function title(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return ($locale === 'en' && $this->title_en) ? $this->title_en : $this->title_vi;
    }

    /** Localized body (HTML) for the current (or given) locale, falling back to Vietnamese. */
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
        return $value ?: Str::limit(trim(strip_tags($this->body($locale))), 140);
    }

    /** Generate a unique URL slug from a title. */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'guide';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
