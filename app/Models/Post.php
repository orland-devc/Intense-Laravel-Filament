<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'status',
        'featured_image',
    ];

    public function getFilamentName(): string
    {
        return $this->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }

    public static function getPublishedPosts()
    {
        return Cache::remember('published_posts', 3600, function () {
            return self::where('status', 'published')
                      ->latest()
                      ->get();
        });
    }

    public static function getCachedPostCount()
    {
        return Cache::remember('post_count', 1800, function () {
            return self::count();
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function () {
            Cache::forget('published_posts');
            Cache::forget('post_count');
        });

        static::updated(function () {
            Cache::forget('published_posts');
            Cache::forget('post_count');
        });

        static::deleted(function () {
            Cache::forget('published_posts');
            Cache::forget('post_count');
        });
    }

}
