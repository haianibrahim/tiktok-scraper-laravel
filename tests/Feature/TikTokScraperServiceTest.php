<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests\Feature;

use Hki98\LaravelTikTokScraper\Tests\TestCase;
use Hki98\LaravelTikTokScraper\Facades\TikTokScraper;
use PHPUnit\Framework\Attributes\Test;

class TikTokScraperServiceTest extends TestCase
{
    #[Test]
    public function it_can_validate_tiktok_urls(): void
    {
        $validUrls = [
            'https://www.tiktok.com/@username/video/1234567890',
            'https://tiktok.com/@username/video/1234567890',
            'https://vm.tiktok.com/ZMeJKQHJH/',
            'https://m.tiktok.com/v/1234567890.html',
        ];

        foreach ($validUrls as $url) {
            $this->assertTrue(TikTokScraper::isValidTikTokUrl($url), "URL should be valid: {$url}");
        }

        $invalidUrls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.instagram.com/p/ABC123/',
            'https://example.com',
            'not-a-url',
            '',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertFalse(TikTokScraper::isValidTikTokUrl($url), "URL should be invalid: {$url}");
        }
    }

    #[Test]
    public function it_can_clear_cache(): void
    {
        // This test verifies that the clearCache method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        TikTokScraper::clearCache();
    }

    #[Test]
    public function it_can_clear_url_cache(): void
    {
        $url = 'https://www.tiktok.com/@username/video/1234567890';
        
        // This test verifies that the clearUrlCache method doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        TikTokScraper::clearUrlCache($url);
    }

    #[Test]
    public function it_can_check_cache_existence(): void
    {
        $url = 'https://www.tiktok.com/@username/video/1234567890';
        
        // Initially, cache should not exist
        $this->assertFalse(TikTokScraper::hasCachedResult($url));
    }

    #[Test]
    public function facade_resolves_correctly(): void
    {
        $scraper = TikTokScraper::getFacadeRoot();
        
        $this->assertInstanceOf(
            \Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface::class,
            $scraper
        );
    }

    #[Test]
    public function service_is_bound_in_container(): void
    {
        $scraper = app(\Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface::class);
        
        $this->assertInstanceOf(
            \Hki98\LaravelTikTokScraper\Services\TikTokScraperService::class,
            $scraper
        );
    }
}
