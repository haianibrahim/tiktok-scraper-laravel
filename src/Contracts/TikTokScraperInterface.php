<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Contracts;

use Hki98\LaravelTikTokScraper\Data\UserInfo;
use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;

interface TikTokScraperInterface
{
    /**
     * Scrape details from a TikTok video or photo URL.
     *
     * @param string $url The TikTok video or photo URL
     * @param bool $useCache Whether to use cached results if available
     * @return VideoDetails The scraped post details
     * @throws TikTokScraperException If scraping fails
     */
    public function scrape(string $url, bool $useCache = true): VideoDetails;

    /**
     * Scrape user/profile information by username or profile URL.
     *
     * @param string $usernameOrUrl A bare username, "@username", or full profile URL
     * @param bool $useCache Whether to use cached results if available
     * @return UserInfo The scraped user profile details
     * @throws TikTokScraperException If scraping fails
     */
    public function scrapeUser(string $usernameOrUrl, bool $useCache = true): UserInfo;

    /**
     * Scrape multiple TikTok URLs.
     *
     * @param array<string> $urls Array of TikTok URLs
     * @param bool $useCache Whether to use cached results if available
     * @return array<VideoDetails> Array of scraped post details
     */
    public function scrapeMultiple(array $urls, bool $useCache = true): array;

    /**
     * Check if a URL is a valid TikTok video, photo, or profile URL.
     *
     * @param string $url The URL to validate
     * @return bool True if valid TikTok URL
     */
    public function isValidTikTokUrl(string $url): bool;

    /**
     * Check if a value is a valid user profile input (username, @username, or profile URL).
     *
     * @param string $value The value to validate
     * @return bool True if valid
     */
    public function isValidUserInput(string $value): bool;

    /**
     * Get cached video/photo details if available.
     *
     * @param string $url The TikTok URL
     * @return VideoDetails|null The cached details or null if not cached
     */
    public function getCachedDetails(string $url): ?VideoDetails;

    /**
     * Get cached user details if available.
     *
     * @param string $usernameOrUrl The username or profile URL
     * @return UserInfo|null The cached user details or null if not cached
     */
    public function getCachedUserDetails(string $usernameOrUrl): ?UserInfo;

    /**
     * Determine whether a cached result exists for the given URL or username.
     *
     * @param string $url The URL or username
     * @return bool
     */
    public function hasCachedResult(string $url): bool;

    /**
     * Clear all cached scraper results.
     */
    public function clearCache(): void;

    /**
     * Clear the cache for a specific URL or username.
     *
     * @param string $url The URL or username to clear
     */
    public function clearUrlCache(string $url): void;

    /**
     * Get scraper statistics.
     *
     * @return array<string, mixed> Statistics about scraper usage
     */
    public function getStatistics(): array;
}
