<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Commands;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Illuminate\Console\Command;

class TestScraperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok-scraper:test {url} {--no-cache : Disable cache for this request}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the TikTok scraper with a given URL';

    /**
     * Execute the console command.
     */
    public function handle(TikTokScraperInterface $scraper): int
    {
        $url = $this->argument('url');
        $useCache = !$this->option('no-cache');

        $this->info("Testing TikTok scraper with URL: {$url}");
        
        if (!$useCache) {
            $this->info('Cache disabled for this request.');
        }

        try {
            $startTime = microtime(true);
            $details = $scraper->scrape($url, $useCache);
            $endTime = microtime(true);

            $this->info('✅ Successfully scraped TikTok video!');
            $this->info(sprintf('⏱️  Processing time: %.2f seconds', $endTime - $startTime));
            $this->newLine();

            // Display video details
            $this->table(
                ['Property', 'Value'],
                [
                    ['Video ID', $details->videoId],
                    ['Username', '@' . $details->username],
                    ['User Nickname', $details->userNickname],
                    ['Description', $this->truncateText($details->description, 100)],
                    ['Views', number_format($details->views)],
                    ['Likes', number_format($details->likes)],
                    ['Comments', number_format($details->comments)],
                    ['Shares', number_format($details->shares)],
                    ['Favorites', number_format($details->favorites)],
                    ['Engagement Rate', sprintf('%.2f%%', $details->getEngagementRate())],
                    ['Canonical URL', $details->canonicalUrl],
                ]
            );

            // Display statistics
            $stats = $scraper->getStatistics();
            $this->newLine();
            $this->info('📊 Scraper Statistics:');
            $this->table(
                ['Metric', 'Count'],
                array_map(fn($key, $value) => [ucfirst(str_replace('_', ' ', $key)), $value], array_keys($stats), $stats)
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to scrape TikTok video:');
            $this->error($e->getMessage());
            
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Truncate text to specified length.
     */
    private function truncateText(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }
}
