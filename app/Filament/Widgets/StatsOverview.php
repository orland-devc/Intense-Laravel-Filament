<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', Post::count())
                ->description('Total number of posts')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
            Stat::make('Published Posts', Post::where('status', 'published')->count())
                ->description('Published posts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Draft Posts', Post::where('status', 'draft')->count())
                ->description('Posts in draft')
                ->descriptionIcon('heroicon-m-pencil')
                ->color('warning'),
        ];
    }
}