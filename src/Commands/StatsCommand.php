<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Commands;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class StatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok-scraper:stats {--clear : Clear all statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show TikTok scraper statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('clear')) {
            return $this->clearStats();
        }

        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Display scraper statistics.
     */
    private function displayStats(): void
    {
        $stats = [
            'Total Requests' => Cache::get('tiktok_scraper_total_requests', 0),
            'Successful Scrapes' => Cache::get('tiktok_scraper_successful_scrapes', 0),
            'Failed Scrapes' => Cache::get('tiktok_scraper_failed_scrapes', 0),
            'Cache Hits' => Cache::get('tiktok_scraper_cache_hits', 0),
            'Rate Limit Hits' => Cache::get('tiktok_scraper_rate_limit_hits', 0),
        ];

        $totalRequests = $stats['Total Requests'];
        $successfulScrapes = $stats['Successful Scrapes'];
        $failedScrapes = $stats['Failed Scrapes'];
        $cacheHits = $stats['Cache Hits'];

        $this->info('TikTok Scraper Statistics');
        $this->info(str_repeat('=', 30));

        $this->table(
            ['Metric', 'Value', 'Percentage'],
            [
                [
                    'Total Requests',
                    number_format($totalRequests),
                    '100%'
                ],
                [
                    'Successful Scrapes',
                    number_format($successfulScrapes),
                    $totalRequests > 0 ? round(($successfulScrapes / $totalRequests) * 100, 2) . '%' : '0%'
                ],
                [
                    'Failed Scrapes',
                    number_format($failedScrapes),
                    $totalRequests > 0 ? round(($failedScrapes / $totalRequests) * 100, 2) . '%' : '0%'
                ],
                [
                    'Cache Hits',
                    number_format($cacheHits),
                    $totalRequests > 0 ? round(($cacheHits / $totalRequests) * 100, 2) . '%' : '0%'
                ],
                [
                    'Rate Limit Hits',
                    number_format($stats['Rate Limit Hits']),
                    $totalRequests > 0 ? round(($stats['Rate Limit Hits'] / $totalRequests) * 100, 2) . '%' : '0%'
                ],
            ]
        );

        // Performance metrics
        if ($totalRequests > 0) {
            $this->newLine();
            $this->info('Performance Metrics');
            $this->info(str_repeat('-', 20));
            
            $successRate = round(($successfulScrapes / $totalRequests) * 100, 2);
            $cacheEfficiency = round(($cacheHits / $totalRequests) * 100, 2);
            
            $this->line("Success Rate: <fg=green>{$successRate}%</>");
            $this->line("Cache Efficiency: <fg=blue>{$cacheEfficiency}%</>");
            
            if ($stats['Rate Limit Hits'] > 0) {
                $rateLimitRate = round(($stats['Rate Limit Hits'] / $totalRequests) * 100, 2);
                $this->line("Rate Limit Impact: <fg=red>{$rateLimitRate}%</>");
            }
        } else {
            $this->newLine();
            $this->comment('No requests have been made yet.');
        }

        // Recent activity
        $this->displayRecentActivity();
    }

    /**
     * Display recent activity.
     */
    private function displayRecentActivity(): void
    {
        $recentUrls = Cache::get('tiktok_scraper_recent_urls', []);
        
        if (!empty($recentUrls)) {
            $this->newLine();
            $this->info('Recent Activity (Last 10 URLs)');
            $this->info(str_repeat('-', 30));
            
            foreach (array_slice($recentUrls, -10) as $entry) {
                $timestamp = date('Y-m-d H:i:s', $entry['timestamp']);
                $status = $entry['success'] ? '<fg=green>SUCCESS</>' : '<fg=red>FAILED</>';
                $this->line("{$timestamp} - {$entry['url']} - {$status}");
            }
        }
    }

    /**
     * Clear all statistics.
     */
    private function clearStats(): int
    {
        if (!$this->confirm('Are you sure you want to clear all statistics?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $keys = [
            'tiktok_scraper_total_requests',
            'tiktok_scraper_successful_scrapes',
            'tiktok_scraper_failed_scrapes',
            'tiktok_scraper_cache_hits',
            'tiktok_scraper_rate_limit_hits',
            'tiktok_scraper_recent_urls',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        $this->info('All statistics have been cleared.');

        return Command::SUCCESS;
    }
}
