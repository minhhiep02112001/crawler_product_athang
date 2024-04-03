<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Services\Crawler_site_1stmanhwa;
use App\Services\Crawlers;
use App\Services\CrawlersAquamanga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Crawler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:data {--function=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $function = $this->option('function');
        $this->$function();
    }
 

    // run 
    // php artisan crawler:data --function=crawler
    public function crawler($page=1)
    { 

        $crawlerService = new Crawlers();
        $crawlerService->index();
    }
  
 
}
