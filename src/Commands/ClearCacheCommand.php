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

            $cleared = $scraper->clearCache($url);
            
            if ($cleared) {
                $this->info("✅ Cache cleared for URL: {$url}");
            } else {
                $this->warn("⚠️  No cache found for URL: {$url}");
            }
        } else {
            if ($this->confirm('Are you sure you want to clear ALL TikTok scraper cache?')) {
                $cleared = $scraper->clearCache();
                
                if ($cleared) {
                    $this->info('✅ All TikTok scraper cache cleared successfully!');
                } else {
                    $this->error('❌ Failed to clear cache');
                    return Command::FAILURE;
                }
            } else {
                $this->info('Cache clearing cancelled.');
                return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }
}
