<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Http\Controllers;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TikTokScraperController extends Controller
{
    public function __construct(
        private readonly TikTokScraperInterface $scraper
    ) {}

    /**
     * Scrape a single TikTok URL.
     */
    public function scrape(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|url',
            'use_cache' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $url = $request->input('url');
        $useCache = $request->boolean('use_cache', true);

        try {
            // Validate TikTok URL
            if (!$this->scraper->isValidTikTokUrl($url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid TikTok URL',
                ], 400);
            }

            $videoDetails = $this->scraper->scrape($url, $useCache);

            return response()->json([
                'success' => true,
                'data' => $videoDetails->toArray(),
            ]);

        } catch (TikTokScraperException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 500);
        }
    }

    /**
     * Scrape multiple TikTok URLs.
     */
    public function bulkScrape(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'urls' => 'required|array|min:1|max:10',
            'urls.*' => 'required|string|url',
            'use_cache' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $urls = $request->input('urls');
        $useCache = $request->boolean('use_cache', true);

        try {
            $results = $this->scraper->scrapeMultiple($urls, $useCache);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($result) => $result->toArray(), $results),
                'total' => count($results),
            ]);

        } catch (TikTokScraperException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 500);
        }
    }

    /**
     * Validate a TikTok URL.
     */
    public function validateUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $url = $request->input('url');
        $isValid = $this->scraper->isValidTikTokUrl($url);

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'url' => $url,
        ]);
    }

    /**
     * Get scraper statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_requests' => Cache::get('tiktok_scraper_total_requests', 0),
            'successful_scrapes' => Cache::get('tiktok_scraper_successful_scrapes', 0),
            'failed_scrapes' => Cache::get('tiktok_scraper_failed_scrapes', 0),
            'cache_hits' => Cache::get('tiktok_scraper_cache_hits', 0),
            'rate_limit_hits' => Cache::get('tiktok_scraper_rate_limit_hits', 0),
        ];

        $totalRequests = $stats['total_requests'];
        
        $calculatedStats = [
            'success_rate' => $totalRequests > 0 ? round(($stats['successful_scrapes'] / $totalRequests) * 100, 2) : 0,
            'cache_efficiency' => $totalRequests > 0 ? round(($stats['cache_hits'] / $totalRequests) * 100, 2) : 0,
            'failure_rate' => $totalRequests > 0 ? round(($stats['failed_scrapes'] / $totalRequests) * 100, 2) : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, $calculatedStats),
        ]);
    }

    /**
     * Clear all cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->scraper->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cache for a specific URL.
     */
    public function clearUrlCache(Request $request, string $url): JsonResponse
    {
        try {
            $decodedUrl = urldecode($url);
            
            if (!$this->scraper->isValidTikTokUrl($decodedUrl)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid TikTok URL',
                ], 400);
            }

            $this->scraper->clearUrlCache($decodedUrl);

            return response()->json([
                'success' => true,
                'message' => 'URL cache cleared successfully',
                'url' => $decodedUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear URL cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => $this->formatBytes($memoryUsage),
                'memory_limit' => $memoryLimit,
                'cache_driver' => config('cache.default'),
            ],
            'scraper' => [
                'service_status' => 'operational',
                'cache_status' => Cache::getStore() ? 'enabled' : 'disabled',
            ],
        ]);
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
