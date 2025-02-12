<x-filament::widget>
    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-lg font-medium">Recent Notifications</h2>
            
            @forelse($this->getUnreadNotifications() as $notification)
                <div class="p-4 bg-gray-100 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium">{{ $notification->data['title'] ?? 'Notification' }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                        
                        <button wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-sm text-primary-600 hover:text-primary-800">
                            Mark as read
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-gray-600">No unread notifications</p>
            @endforelse
        </div>
    </x-filament::card>
</x-filament::widget>