<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:clear-all-caches';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    protected $signature = 'cache:clear-all';
    protected $description = 'Clear all caches (view, route, config, application)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('view:clear');
        $this->info('View cache cleared!');

        Artisan::call('route:clear');
        $this->info('Route cache cleared!');

        Artisan::call('config:clear');
        $this->info('Config cache cleared!');

        Artisan::call('cache:clear');
        $this->info('Application cache cleared!');

        return Command::SUCCESS;
    }
}
