<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Contracts;

use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;

interface TikTokScraperInterface
{
    /**
     * Scrape video details from a TikTok URL.
     *
     * @param string $url The TikTok video URL
     * @param bool $useCache Whether to use cached results if available
     * @return VideoDetails The scraped video details
     * @throws TikTokScraperException If scraping fails
     */
    public function scrape(string $url, bool $useCache = true): VideoDetails;

    /**
     * Scrape multiple TikTok URLs.
     *
     * @param array<string> $urls Array of TikTok video URLs
     * @param bool $useCache Whether to use cached results if available
     * @return array<VideoDetails> Array of scraped video details
     */
    public function scrapeMultiple(array $urls, bool $useCache = true): array;

    /**
     * Check if a URL is a valid TikTok video URL.
     *
     * @param string $url The URL to validate
     * @return bool True if valid TikTok URL
     */
    public function isValidTikTokUrl(string $url): bool;

    /**
     * Get cached video details if available.
     *
     * @param string $url The TikTok video URL
     * @return VideoDetails|null The cached video details or null if not cached
     */
    public function getCachedDetails(string $url): ?VideoDetails;

    /**
     * Clear cache for a specific URL or all cache.
     *
     * @param string|null $url The URL to clear cache for, or null to clear all
     * @return bool True if cache was cleared successfully
     */
    public function clearCache(?string $url = null): bool;

    /**
     * Get scraper statistics.
     *
     * @return array<string, mixed> Statistics about scraper usage
     */
    public function getStatistics(): array;
}
