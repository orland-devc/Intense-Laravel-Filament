<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CacheMonitor extends Widget
{
    protected static string $view = 'filament.widgets.cache-monitor';
    
    public function clearCache()
    {
        Cache::flush();
        $this->notify('success', 'Cache cleared successfully');
    }

    public function getCacheStats()
    {
        return [
            'total_posts_cache' => Cache::has('stats_total_posts'),
            'published_posts_cache' => Cache::has('stats_published_posts'),
            'draft_posts_cache' => Cache::has('stats_draft_posts'),
        ];
    }
}