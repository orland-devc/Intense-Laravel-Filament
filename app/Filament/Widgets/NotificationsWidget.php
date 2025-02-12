<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notifications-widget';
    
    public function getUnreadNotifications()
    {
        return auth()->user()->unreadNotifications()->take(5)->get(); // Undefined method 'user'.
    }

    public function markAsRead(string $notificationId)
    {
        $notification = DatabaseNotification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('notification-read');
        }
    }
}