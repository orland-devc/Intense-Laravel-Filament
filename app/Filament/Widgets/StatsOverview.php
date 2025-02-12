<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', $this->getCachedTotalPosts())
                ->description('Total number of posts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
            Stat::make('Published Posts', $this->getCachedPublishedPosts())
                ->description('Published posts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Draft Posts', $this->getCachedDraftPosts())
                ->description('Posts in draft')
                ->descriptionIcon('heroicon-m-pencil')
                ->color('warning'),
        ];
    }

    private function getCachedTotalPosts()
    {
        return Cache::remember('stats_total_posts', 300, function () {
            return Post::count();
        });
    }

    private function getCachedPublishedPosts()
    {
        return Cache::remember('stats_published_posts', 300, function () {
            return Post::where('status', 'published')->count();
        });
    }

    private function getCachedDraftPosts()
    {
        return Cache::remember('stats_draft_posts', 300, function () {
            return Post::where('status', 'draft')->count();
        });
    }
}