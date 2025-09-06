<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Commands;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Illuminate\Console\Command;

class BulkScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok-scraper:bulk {file} {--output= : Output file path} {--format=json : Output format (json|csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk scrape TikTok URLs from a file';

    /**
     * Execute the console command.
     */
    public function handle(TikTokScraperInterface $scraper): int
    {
        $filePath = $this->argument('file');
        $outputPath = $this->option('output');
        $format = $this->option('format');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $urls = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($urls)) {
            $this->error('No URLs found in the file.');
            return Command::FAILURE;
        }

        $this->info("Found " . count($urls) . " URLs to scrape.");

        $results = [];
        $progressBar = $this->output->createProgressBar(count($urls));
        $progressBar->start();

        foreach ($urls as $url) {
            try {
                $details = $scraper->scrape(trim($url));
                $results[] = $details->toArray();
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("\nFailed to scrape {$url}: " . $e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Successfully scraped " . count($results) . " videos.");

        if ($outputPath) {
            if ($format === 'csv') {
                $this->writeCsv($results, $outputPath);
            } else {
                $this->writeJson($results, $outputPath);
            }
            
            $this->info("Results saved to: {$outputPath}");
        } else {
            $this->table(
                ['Video ID', 'Username', 'Views', 'Likes', 'Comments'],
                array_map(fn($result) => [
                    $result['video_id'],
                    '@' . $result['username'],
                    number_format($result['views']),
                    number_format($result['likes']),
                    number_format($result['comments']),
                ], $results)
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Write results to JSON file.
     */
    private function writeJson(array $results, string $path): void
    {
        file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Write results to CSV file.
     */
    private function writeCsv(array $results, string $path): void
    {
        if (empty($results)) {
            return;
        }

        $handle = fopen($path, 'w');
        
        // Write header
        fputcsv($handle, array_keys($results[0]));
        
        // Write data
        foreach ($results as $result) {
            fputcsv($handle, $result);
        }
        
        fclose($handle);
    }
}
