<x-filament::widget>
    <x-filament::card>
        <div class="space-y-4">
            <h2 class="text-lg font-medium">Cache Monitor</h2>
            
            <div class="space-y-2">
                @foreach($this->getCacheStats() as $key => $exists)
                    <div class="flex justify-between items-center">
                        <span>{{ str_replace('_', ' ', Str::title($key)) }}</span>
                        <span class="px-2 py-1 text-xs rounded-full {{ $exists ? 'bg-success-500 text-white' : 'bg-danger-500 text-white' }}">
                            {{ $exists ? 'Cached' : 'Not Cached' }}
                        </span>
                    </div>
                @endforeach
            </div>

            <button wire:click="clearCache" class="btn btn-danger w-full">
                Clear All Cache
            </button>
        </div>
    </x-filament::card>
</x-filament::widget>