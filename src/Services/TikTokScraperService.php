<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Events\VideoScraped;
use Hki98\LaravelTikTokScraper\Events\ScrapingFailed;
use Hki98\LaravelTikTokScraper\Events\RateLimitHit;
use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;
use Hki98\LaravelTikTokScraper\Exceptions\InvalidUrlException;
use Hki98\LaravelTikTokScraper\Exceptions\HttpRequestException;
use Hki98\LaravelTikTokScraper\Exceptions\EmptyResponseException;
use Hki98\LaravelTikTokScraper\Exceptions\ParseException;
use Hki98\LaravelTikTokScraper\Exceptions\RateLimitException;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TikTokScraperService implements TikTokScraperInterface
{
    private const REHYDRATION_SCRIPT_ID = '__UNIVERSAL_DATA_FOR_REHYDRATION__';

    private CacheRepository $cache;
    private array $config;
    private array $statistics = [
        'total_requests' => 0,
        'successful_scrapes' => 0,
        'failed_scrapes' => 0,
        'cache_hits' => 0,
        'rate_limit_hits' => 0,
    ];

    public function __construct(
        private readonly ClientInterface $httpClient,
        CacheManager $cacheManager,
        array $config
    ) {
        $this->config = $config;
        $this->cache = $cacheManager->store($config['cache']['store'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function scrape(string $url, bool $useCache = true): VideoDetails
    {
        $this->statistics['total_requests']++;

        // Validate URL
        if (!$this->isValidTikTokUrl($url)) {
            $this->statistics['failed_scrapes']++;
            throw new InvalidUrlException("Invalid TikTok URL: {$url}");
        }

        // Check rate limit
        if ($this->isRateLimited()) {
            $this->statistics['rate_limit_hits']++;
            $this->dispatchEvent(new RateLimitHit($url));
            throw new RateLimitException('Rate limit exceeded. Please try again later.');
        }

        // Try cache first
        if ($useCache && $this->config['cache']['enabled']) {
            $cached = $this->getCachedDetails($url);
            if ($cached) {
                $this->statistics['cache_hits']++;
                return $cached;
            }
        }

        try {
            // Scrape video details
            $videoDetails = $this->doScrape($url);
            
            // Cache the result
            if ($this->config['cache']['enabled']) {
                $this->cacheVideoDetails($url, $videoDetails);
            }

            // Dispatch success event
            $this->dispatchEvent(new VideoScraped($url, $videoDetails));
            
            // Update statistics
            $this->statistics['successful_scrapes']++;
            
            // Log successful scrape
            $this->log('info', 'Video scraped successfully', [
                'url' => $url,
                'video_id' => $videoDetails->videoId,
                'username' => $videoDetails->username,
            ]);

            return $videoDetails;

        } catch (TikTokScraperException $e) {
            $this->statistics['failed_scrapes']++;
            $this->dispatchEvent(new ScrapingFailed($url, $e));
            
            $this->log('error', 'Scraping failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function scrapeMultiple(array $urls, bool $useCache = true): array
    {
        $results = [];
        
        foreach ($urls as $url) {
            try {
                $results[] = $this->scrape($url, $useCache);
            } catch (TikTokScraperException $e) {
                // Continue with other URLs even if one fails
                continue;
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidTikTokUrl(string $url): bool
    {
        $patterns = [
            '/^https?:\/\/(www\.)?(tiktok\.com|vm\.tiktok\.com|m\.tiktok\.com)\/.*$/i',
            '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/video\/\d+\??.*$/i',
            '/^https?:\/\/vm\.tiktok\.com\/[\w]+\/?$/i',
            '/^https?:\/\/m\.tiktok\.com\/v\/\d+\.html\??.*$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        if ($this->config['cache']['enabled']) {
            $this->cache->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearUrlCache(string $url): void
    {
        if ($this->config['cache']['enabled']) {
            $cacheKey = $this->getCacheKey($url);
            $this->cache->forget($cacheKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCachedResult(string $url): bool
    {
        if (!$this->config['cache']['enabled']) {
            return false;
        }

        $cacheKey = $this->getCacheKey($url);
        return $this->cache->has($cacheKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedDetails(string $url): ?VideoDetails
    {
        if (!$this->config['cache']['enabled']) {
            return null;
        }

        $cacheKey = $this->getCacheKey($url);
        $cached = $this->cache->get($cacheKey);

        if ($cached && is_array($cached)) {
            return VideoDetails::fromArray($cached);
        }

        return null;
    }

    /**
     * Perform the actual scraping.
     */
    private function doScrape(string $url): VideoDetails
    {
        try {
            $response = $this->httpClient->get($url, [
                'headers' => $this->config['http_client']['headers'] ?? [],
                'timeout' => $this->config['http_client']['timeout'] ?? 30,
                'connect_timeout' => $this->config['http_client']['connect_timeout'] ?? 10,
            ]);

            $html = $response->getBody()->getContents();

            if (empty($html)) {
                throw new EmptyResponseException('Empty response body from TikTok.');
            }

            return $this->parseVideoDetails($html, $url);

        } catch (GuzzleException $e) {
            throw new HttpRequestException('Network error fetching page: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse video details from HTML.
     */
    private function parseVideoDetails(string $html, string $url): VideoDetails
    {
        // Find the script tag containing video data
        $pattern = '/<script[^>]*id="' . self::REHYDRATION_SCRIPT_ID . '"[^>]*>(.*?)<\/script>/s';
        if (!preg_match($pattern, $html, $matches)) {
            throw new ParseException('Unable to locate embedded data on the page.');
        }

        $jsonData = $matches[1];
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseException('Failed to decode embedded JSON.');
        }

        return $this->extractVideoDetailsFromData($data, $url);
    }

    /**
     * Extract video details from parsed data.
     */
    private function extractVideoDetailsFromData(array $data, string $url): VideoDetails
    {
        // Navigate through the data structure to find video information
        $itemInfo = $data['__DEFAULT_SCOPE__']['webapp.video-detail']['itemInfo']['itemStruct'] ?? null;
        
        if (!$itemInfo) {
            throw new ParseException('Video information not found in the data structure.');
        }

        // Extract video details
        $videoId = $itemInfo['id'] ?? '';
        $desc = $itemInfo['desc'] ?? '';
        $createTime = $itemInfo['createTime'] ?? 0;
        
        // Author information
        $author = $itemInfo['author'] ?? [];
        $username = $author['uniqueId'] ?? '';
        $displayName = $author['nickname'] ?? '';
        $avatarUrl = $author['avatarThumb'] ?? '';

        // Statistics
        $stats = $itemInfo['stats'] ?? [];
        $views = $stats['playCount'] ?? 0;
        $likes = $stats['diggCount'] ?? 0;
        $comments = $stats['commentCount'] ?? 0;
        $shares = $stats['shareCount'] ?? 0;

        // Video information
        $video = $itemInfo['video'] ?? [];
        $videoUrl = $video['playAddr'] ?? '';
        $coverUrl = $video['cover'] ?? '';
        $duration = $video['duration'] ?? 0;

        // Music information
        $music = $itemInfo['music'] ?? [];
        $musicTitle = $music['title'] ?? '';
        $musicAuthor = $music['authorName'] ?? '';

        if (empty($videoId)) {
            throw new ParseException('Please enter a valid TikTok URL!');
        }

        return new VideoDetails(
            videoId: $videoId,
            url: $url,
            title: $desc,
            description: $desc,
            username: $username,
            displayName: $displayName,
            avatarUrl: $avatarUrl,
            views: (int) $views,
            likes: (int) $likes,
            comments: (int) $comments,
            shares: (int) $shares,
            musicTitle: $musicTitle,
            musicAuthor: $musicAuthor,
            videoUrl: $videoUrl,
            coverUrl: $coverUrl,
            duration: (int) $duration,
            createdAt: date('c', $createTime)
        );
    }

    /**
     * Cache video details.
     */
    private function cacheVideoDetails(string $url, VideoDetails $videoDetails): void
    {
        $cacheKey = $this->getCacheKey($url);
        $ttl = $this->config['cache']['ttl'] ?? 3600;
        
        $this->cache->put($cacheKey, $videoDetails->toArray(), $ttl);
    }

    /**
     * Generate cache key for URL.
     */
    private function getCacheKey(string $url): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'tiktok_scraper';
        return $prefix . ':' . md5($url);
    }

    /**
     * Check if requests are rate limited.
     */
    private function isRateLimited(): bool
    {
        if (!$this->config['rate_limiting']['enabled']) {
            return false;
        }

        $key = 'tiktok_scraper_rate_limit';
        $maxAttempts = $this->config['rate_limiting']['max_attempts'] ?? 60;
        
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Log a message.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->config['logging']['enabled'] ?? true) {
            return;
        }

        $channel = $this->config['logging']['channel'] ?? 'default';
        Log::channel($channel)->log($level, $message, $context);
    }

    /**
     * Dispatch an event.
     */
    private function dispatchEvent(object $event): void
    {
        if (!$this->config['events']['enabled'] ?? true) {
            return;
        }

        Event::dispatch($event);
    }
}
