<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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

        if (!$this->isValidTikTokUrl($url)) {
            $this->statistics['failed_scrapes']++;
            throw InvalidUrlException::forUrl($url);
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
            $this->assertTikTokUrl($url);
            $html = $this->fetchHtml($url);
            $json = $this->extractEmbeddedJson($html);
            $data = $this->normalizeData($json);

            $details = VideoDetails::fromArray($data);

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
    public function scrapeUser(string $usernameOrUrl, bool $useCache = true): UserInfo
    {
        $this->statistics['total_requests']++;

        if (!$this->isValidUserInput($usernameOrUrl)) {
            $this->statistics['failed_scrapes']++;
            throw InvalidUrlException::forUrl($usernameOrUrl);
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
            $url = $this->buildUserUrl($usernameOrUrl);
            $html = $this->fetchHtml($url);
            $json = $this->extractEmbeddedJson($html);
            $info = $this->normalizeUserData($json);

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
        } catch (TikTokScraperException $e) {
            $this->statistics['failed_scrapes']++;
            $this->dispatchEvent(new ScrapingFailed($usernameOrUrl, $e));
            $this->log('error', 'User scraping failed', [
                'input' => $usernameOrUrl,
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

    /* =========================================================================
       Ported Core Scraper Engine Methods
       ========================================================================= */

    private function assertTikTokUrl(string $url): void
    {
        if (!preg_match('#^https?://([\w.-]+\.)?tiktok\.com/.*#i', $url)) {
            throw InvalidUrlException::forUrl($url);
        }

        if (preg_match('#^https?://(www\.)?tiktok\.com/?$#i', $url)) {
            throw InvalidUrlException::forUrl($url);
        }
    }

    private function fetchHtml(string $url): string
    {
        try {
            $res = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => $this->userAgent(),
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                ],
                'allow_redirects' => [
                    'max' => 10,
                    'strict' => false,
                    'referer' => true,
                    'track_redirects' => true
                ],
                'decode_content' => true,
            ]);
        } catch (GuzzleException $e) {
            throw HttpRequestException::from($e);
        }

        $body = (string) $res->getBody();
        if ($body === '') {
            throw EmptyResponseException::create();
        }
        return $body;
    }

    private function extractEmbeddedJson(string $html): array
    {
        $pattern = '/<script[^>]*id="' . preg_quote(self::REHYDRATION_SCRIPT_ID, '/') . '"[^>]*>(.*?)<\/script>/si';
        if (!preg_match($pattern, $html, $m)) {
            throw ParseException::unableToLocateData();
        }

        $jsonRaw = html_entity_decode(trim($m[1]));
        $decoded = json_decode($jsonRaw, true);
        if (!is_array($decoded)) {
            throw ParseException::jsonDecode();
        }
        return $decoded;
    }

    private function normalizeData(array $decoded): array
    {
        $flatNodes = [];
        $this->flattenArray($decoded, $flatNodes);

        $canonical = $this->findFirstMatch($flatNodes, function($node) {
            return is_string($node) && preg_match('~https?://www\.tiktok\.com/@[^/]*/(video|photo)/\d+~i', $node);
        });

        $item = $this->findFirstMatch($flatNodes, function($node) {
            return is_array($node) && (
                (isset($node['imagePost']) || isset($node['video'])) &&
                isset($node['author']) &&
                isset($node['stats'])
            );
        });

        if (!is_array($item)) {
            $item = $this->findFirstMatch($flatNodes, function($node) {
                return is_array($node) && isset($node['itemStruct']);
            });
            if (is_array($item) && isset($item['itemStruct'])) {
                $item = $item['itemStruct'];
            }
        }

        if (!is_array($item)) {
            foreach ($decoded as $object) {
                if (!is_array($object)) {
                    continue;
                }

                $canonical = $canonical ?? ($object['seo.abtest']['canonical'] ?? '');

                $item = $object['webapp.video-detail']['itemInfo']['itemStruct'] ?? null;
                if (!is_array($item)) {
                    $item = $object['webapp.photo-detail']['itemInfo']['itemStruct'] ?? null;
                }
                if (!is_array($item)) {
                    $item = $object['itemInfo']['itemStruct'] ?? null;
                }

                if (is_array($item)) {
                    break;
                }
            }
        }

        if (!is_array($item) && is_string($canonical) && $canonical !== '') {
            return $this->extractMinimalPhotoData($canonical);
        }

        if (!is_array($item)) {
            throw ParseException::invalidStructure();
        }

        $videoId = (string)($item['id'] ?? '');
        $author = $item['author'] ?? [];
        $stats = $item['stats'] ?? [];

        $username = (string)($author['uniqueId'] ?? '');
        $userId = (string)($author['id'] ?? '');

        if ($videoId !== '' && $userId !== '' && $username !== '') {
            $thumbnail = '';
            
            if (isset($item['imagePost']['images']) && is_array($item['imagePost']['images'])) {
                $firstImage = $item['imagePost']['images'][0] ?? null;
                if (is_array($firstImage)) {
                    $thumbnail = $this->extractFirstUrl($firstImage['imageURL'] ?? $firstImage['displayImage'] ?? null);
                }
            } else {
                $video = $item['video'] ?? [];
                $thumbnail = (string)($video['dynamicCover'] ?? $video['cover'] ?? '');
            }

            return [
                'canonical' => (string)($canonical ?? ''),
                'videoId' => $videoId,
                'description' => (string)($item['desc'] ?? ''),
                'user' => (string)($author['nickname'] ?? ''),
                'username' => $username,
                'userId' => $userId,
                'thumbnail' => $thumbnail,
                'views' => (int)($stats['playCount'] ?? 0),
                'likes' => (int)($stats['diggCount'] ?? 0),
                'comments' => (int)($stats['commentCount'] ?? 0),
                'shares' => (int)($stats['shareCount'] ?? 0),
                'favorites' => (int)($stats['collectCount'] ?? 0),
            ];
        }

        throw ParseException::invalidStructure();
    }

    private function normalizeUserData(array $decoded): UserInfo
    {
        $scope = $decoded['__DEFAULT_SCOPE__'] ?? $decoded;
        if (!is_array($scope)) {
            throw ParseException::invalidStructure();
        }

        $userInfo = $scope['webapp.user-detail']['userInfo'] ?? null;

        if (!is_array($userInfo)) {
            $flat = [];
            $this->flattenArray($decoded, $flat);
            $userInfo = $this->findFirstMatch($flat, function ($node) {
                return is_array($node)
                    && isset($node['user']) && is_array($node['user'])
                    && isset($node['user']['secUid'])
                    && isset($node['stats']) && is_array($node['stats']);
            });
        }

        if (!is_array($userInfo) || !isset($userInfo['user']) || !is_array($userInfo['user'])) {
            throw ParseException::invalidStructure();
        }

        $user = $userInfo['user'];
        $stats = is_array($userInfo['stats'] ?? null) ? $userInfo['stats'] : [];
        $shareMeta = is_array($userInfo['shareMeta'] ?? null) ? $userInfo['shareMeta'] : [];

        $username = (string)($user['uniqueId'] ?? '');
        $userId = (string)($user['id'] ?? '');
        $secUid = (string)($user['secUid'] ?? '');

        if ($username === '' || $userId === '' || $secUid === '') {
            throw ParseException::invalidStructure();
        }

        return new UserInfo(
            userId: $userId,
            secUid: $secUid,
            username: $username,
            nickname: (string)($user['nickname'] ?? ''),
            signature: (string)($user['signature'] ?? ''),
            avatarThumb: (string)($user['avatarThumb'] ?? ''),
            avatarMedium: (string)($user['avatarMedium'] ?? ''),
            avatarLarger: (string)($user['avatarLarger'] ?? ''),
            verified: (bool)($user['verified'] ?? false),
            privateAccount: (bool)($user['privateAccount'] ?? false),
            createTime: (int)($user['createTime'] ?? 0),
            region: (string)($user['region'] ?? ''),
            followerCount: (int)($stats['followerCount'] ?? 0),
            followingCount: (int)($stats['followingCount'] ?? 0),
            heartCount: (int)($stats['heartCount'] ?? ($stats['heart'] ?? 0)),
            videoCount: (int)($stats['videoCount'] ?? 0),
            diggCount: (int)($stats['diggCount'] ?? 0),
            friendCount: (int)($stats['friendCount'] ?? 0),
            profileUrl: 'https://www.tiktok.com/@' . $username,
            shareTitle: (string)($shareMeta['title'] ?? ''),
            shareDesc: (string)($shareMeta['desc'] ?? ''),
        );
    }

    private function extractMinimalPhotoData(string $canonical): array
    {
        if (preg_match('~https?://www\.tiktok\.com/@([^/]*)/(video|photo)/(\d+)~i', $canonical, $matches)) {
            $username = $matches[1];
            $postId = $matches[3];
            
            return [
                'canonical' => $canonical,
                'videoId' => $postId,
                'description' => '',
                'user' => '',
                'username' => $username,
                'userId' => '',
                'thumbnail' => '',
                'views' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
                'favorites' => 0,
            ];
        }

        throw ParseException::invalidStructure();
    }

    private function buildUserUrl(string $usernameOrUrl): string
    {
        $value = trim($usernameOrUrl);
        if ($value === '') {
            throw InvalidUrlException::forUrl($usernameOrUrl);
        }

        if (preg_match('~^https?://~i', $value)) {
            if (!preg_match('~^https?://([\w.-]+\.)?tiktok\.com/@[^/?\#]+/?$~i', $value)) {
                throw InvalidUrlException::forUrl($usernameOrUrl);
            }
            return $value;
        }

        $username = ltrim($value, '@');
        if (!preg_match('/^[A-Za-z0-9._]{1,24}$/', $username)) {
            throw InvalidUrlException::forUrl($usernameOrUrl);
        }

        return 'https://www.tiktok.com/@' . $username;
    }

    private function flattenArray($node, array &$output): void
    {
        $output[] = $node;
        if (is_array($node)) {
            foreach ($node as $value) {
                $this->flattenArray($value, $output);
            }
        }
    }

    private function findFirstMatch(array $nodes, callable $predicate)
    {
        foreach ($nodes as $node) {
            if ($predicate($node)) {
                return $node;
            }
        }
        return null;
    }

    private function extractFirstUrl($value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            if (isset($value['urlList']) && is_array($value['urlList'])) {
                $first = $value['urlList'][0] ?? null;
                if (is_string($first)) {
                    return $first;
                }
            }
            $first = reset($value);
            if (is_string($first)) {
                return $first;
            }
        }
        return '';
    }

    private function userAgent(): string
    {
        return $this->config['http']['user_agent']
            ?? $this->config['http_client']['user_agent']
            ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0';
    }
}
