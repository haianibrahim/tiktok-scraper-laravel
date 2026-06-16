<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Commands;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok-scraper:clear-cache {url? : Specific URL to clear cache for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear TikTok scraper cache for a specific URL or all cached data';

    /**
     * Execute the console command.
     */
    public function handle(TikTokScraperInterface $scraper): int
    {
        $url = $this->argument('url');

        if ($url) {
            if (!$scraper->isValidTikTokUrl($url)) {
                $this->error("Invalid TikTok URL: {$url}");
                return Command::FAILURE;
            }

            $scraper->clearUrlCache($url);
            $this->info("✅ Cache cleared for URL: {$url}");
        } else {
            if ($this->confirm('Are you sure you want to clear ALL TikTok scraper cache?')) {
                $scraper->clearCache();
                $this->info('✅ All TikTok scraper cache cleared successfully!');
            } else {
                $this->info('Cache clearing cancelled.');
                return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }
}
