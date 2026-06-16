<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Services;

use GuzzleHttp\ClientInterface;
use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Data\UserInfo;
use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Events\RateLimitHit;
use Hki98\LaravelTikTokScraper\Events\ScrapingFailed;
use Hki98\LaravelTikTokScraper\Events\UserScraped;
use Hki98\LaravelTikTokScraper\Events\VideoScraped;
use Hki98\LaravelTikTokScraper\Exceptions\EmptyResponseException;
use Hki98\LaravelTikTokScraper\Exceptions\HttpRequestException;
use Hki98\LaravelTikTokScraper\Exceptions\InvalidUrlException;
use Hki98\LaravelTikTokScraper\Exceptions\ParseException;
use Hki98\LaravelTikTokScraper\Exceptions\RateLimitException;
use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;
use Hki98\TikTok\Exception\EmptyResponseException as NativeEmptyResponseException;
use Hki98\TikTok\Exception\HttpRequestException as NativeHttpRequestException;
use Hki98\TikTok\Exception\InvalidUrlException as NativeInvalidUrlException;
use Hki98\TikTok\Exception\ParseException as NativeParseException;
use Hki98\TikTok\Exception\TikTokScraperException as NativeTikTokScraperException;
use Hki98\TikTok\TikTokScraper as NativeScraper;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TikTokScraperService implements TikTokScraperInterface
{
    private CacheRepository $cache;
    private array $config;
    private NativeScraper $scraper;
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
        $this->scraper = new NativeScraper($this->httpClient);
    }

    /**
     * {@inheritdoc}
     */
    public function scrape(string $url, bool $useCache = true): VideoDetails
    {
        $this->statistics['total_requests']++;

        if (!$this->isValidTikTokUrl($url)) {
            $this->statistics['failed_scrapes']++;
            throw new InvalidUrlException("Invalid TikTok URL: {$url}");
        }

        $this->guardRateLimit($url);

        if ($useCache && $this->cacheEnabled()) {
            $cached = $this->getCachedDetails($url);
            if ($cached !== null) {
                $this->statistics['cache_hits']++;
                return $cached;
            }
        }

        try {
            $native = $this->scraper->scrape($url);
            $details = VideoDetails::fromArray($native->toArray());

            if ($this->cacheEnabled()) {
                $this->cache->put($this->getCacheKey($url), $details->toArray(), $this->cacheTtl());
            }

            $this->dispatchEvent(new VideoScraped($url, $details));
            $this->statistics['successful_scrapes']++;
            $this->log('info', 'Video scraped successfully', [
                'url' => $url,
                'video_id' => $details->videoId,
                'username' => $details->username,
            ]);

            return $details;
        } catch (NativeTikTokScraperException $e) {
            $this->statistics['failed_scrapes']++;
            $mapped = $this->mapNativeException($e);
            $this->dispatchEvent(new ScrapingFailed($url, $mapped));
            $this->log('error', 'Scraping failed', [
                'url' => $url,
                'error' => $mapped->getMessage(),
                'exception' => get_class($mapped),
            ]);

            throw $mapped;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function scrapeUser(string $usernameOrUrl, bool $useCache = true): UserInfo
    {
        $this->statistics['total_requests']++;

        if (!$this->isValidUserInput($usernameOrUrl)) {
            $this->statistics['failed_scrapes']++;
            throw new InvalidUrlException("Invalid TikTok username or profile URL: {$usernameOrUrl}");
        }

        $this->guardRateLimit($usernameOrUrl);

        $cacheKey = $this->getCacheKey('user:' . $usernameOrUrl);

        if ($useCache && $this->cacheEnabled()) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                $this->statistics['cache_hits']++;
                return UserInfo::fromArray($cached);
            }
        }

        try {
            $native = $this->scraper->scrapeUser($usernameOrUrl);
            $info = UserInfo::fromArray($native->toArray());

            if ($this->cacheEnabled()) {
                $this->cache->put($cacheKey, $info->toArray(), $this->cacheTtl());
            }

            $this->dispatchEvent(new UserScraped($usernameOrUrl, $info));
            $this->statistics['successful_scrapes']++;
            $this->log('info', 'User profile scraped successfully', [
                'input' => $usernameOrUrl,
                'user_id' => $info->userId,
                'username' => $info->username,
            ]);

            return $info;
        } catch (NativeTikTokScraperException $e) {
            $this->statistics['failed_scrapes']++;
            $mapped = $this->mapNativeException($e);
            $this->dispatchEvent(new ScrapingFailed($usernameOrUrl, $mapped));
            $this->log('error', 'User scraping failed', [
                'input' => $usernameOrUrl,
                'error' => $mapped->getMessage(),
                'exception' => get_class($mapped),
            ]);

            throw $mapped;
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
                // Continue with other URLs even if one fails.
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
            // Video posts
            '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/video\/\d+\??.*$/i',
            // Photo posts
            '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/photo\/\d+\??.*$/i',
            // User profiles
            '/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/?$/i',
            // Short URLs
            '/^https?:\/\/vm\.tiktok\.com\/[\w]+\/?$/i',
            '/^https?:\/\/m\.tiktok\.com\/v\/\d+\.html\??.*$/i',
            // Generic tiktok.com fallback
            '/^https?:\/\/(www\.)?(tiktok\.com|vm\.tiktok\.com|m\.tiktok\.com)\/.+$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the given value is a valid user profile input:
     * a bare username, "@username", or a full profile URL.
     */
    public function isValidUserInput(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return (bool) preg_match('/^https?:\/\/(www\.)?tiktok\.com\/@[\w.-]+\/?$/i', $value);
        }

        $username = ltrim($value, '@');

        return (bool) preg_match('/^[A-Za-z0-9._]{1,24}$/', $username);
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedDetails(string $url): ?VideoDetails
    {
        if (!$this->cacheEnabled()) {
            return null;
        }

        $cached = $this->cache->get($this->getCacheKey($url));

        if (is_array($cached)) {
            return VideoDetails::fromArray($cached);
        }

        return null;
    }

    /**
     * Get cached user details if available.
     */
    public function getCachedUserDetails(string $usernameOrUrl): ?UserInfo
    {
        if (!$this->cacheEnabled()) {
            return null;
        }

        $cached = $this->cache->get($this->getCacheKey('user:' . $usernameOrUrl));

        if (is_array($cached)) {
            return UserInfo::fromArray($cached);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        if ($this->cacheEnabled()) {
            $this->cache->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearUrlCache(string $url): void
    {
        if ($this->cacheEnabled()) {
            $this->cache->forget($this->getCacheKey($url));
            $this->cache->forget($this->getCacheKey('user:' . $url));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCachedResult(string $url): bool
    {
        if (!$this->cacheEnabled()) {
            return false;
        }

        return $this->cache->has($this->getCacheKey($url))
            || $this->cache->has($this->getCacheKey('user:' . $url));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        $total = $this->statistics['total_requests'];

        return array_merge($this->statistics, [
            'success_rate' => $total > 0
                ? round(($this->statistics['successful_scrapes'] / $total) * 100, 2)
                : 0.0,
            'failure_rate' => $total > 0
                ? round(($this->statistics['failed_scrapes'] / $total) * 100, 2)
                : 0.0,
            'cache_efficiency' => $total > 0
                ? round(($this->statistics['cache_hits'] / $total) * 100, 2)
                : 0.0,
        ]);
    }

    /**
     * Throw if requests are currently rate limited; otherwise record a hit.
     */
    private function guardRateLimit(string $context): void
    {
        if (!($this->config['rate_limiting']['enabled'] ?? false)) {
            return;
        }

        $key = $this->config['rate_limiting']['prefix'] ?? 'tiktok_scraper_rate_limit';
        $maxAttempts = (int) ($this->config['rate_limiting']['max_attempts'] ?? 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->statistics['rate_limit_hits']++;
            $this->dispatchEvent(new RateLimitHit($context));
            throw new RateLimitException('Rate limit exceeded. Please try again later.');
        }

        $decay = (int) ($this->config['rate_limiting']['decay_seconds']
            ?? (($this->config['rate_limiting']['decay_minutes'] ?? 1) * 60));
        RateLimiter::hit($key, $decay);
    }

    private function cacheEnabled(): bool
    {
        return (bool) ($this->config['cache']['enabled'] ?? false);
    }

    private function cacheTtl(): int
    {
        return (int) ($this->config['cache']['ttl'] ?? 3600);
    }

    private function getCacheKey(string $value): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'tiktok_scraper';

        return $prefix . ':' . md5($value);
    }

    /**
     * Map a native scraper exception to the Laravel package exception hierarchy.
     */
    private function mapNativeException(NativeTikTokScraperException $e): TikTokScraperException
    {
        return match (true) {
            $e instanceof NativeInvalidUrlException => new InvalidUrlException($e->getMessage(), (int) $e->getCode(), $e),
            $e instanceof NativeHttpRequestException => new HttpRequestException($e->getMessage(), (int) $e->getCode(), $e),
            $e instanceof NativeEmptyResponseException => new EmptyResponseException($e->getMessage(), (int) $e->getCode(), $e),
            $e instanceof NativeParseException => new ParseException($e->getMessage(), (int) $e->getCode(), $e),
            default => new TikTokScraperException($e->getMessage(), (int) $e->getCode(), $e),
        };
    }

    /**
     * Log a message when logging is enabled.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $channel = $this->config['logging']['channel'] ?? null;

        if ($channel) {
            Log::channel($channel)->log($level, $message, $context);
        } else {
            Log::log($level, $message, $context);
        }
    }

    /**
     * Dispatch an event when events are enabled.
     */
    private function dispatchEvent(object $event): void
    {
        $eventsEnabled = $this->config['events']['enabled']
            ?? $this->config['events']['dispatch_events']
            ?? true;

        if (!$eventsEnabled) {
            return;
        }

        Event::dispatch($event);
    }
}
