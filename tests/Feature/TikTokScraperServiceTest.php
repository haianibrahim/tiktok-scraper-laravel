<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests\Feature;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Exceptions\InvalidUrlException;
use Hki98\LaravelTikTokScraper\Facades\TikTokScraper;
use Hki98\LaravelTikTokScraper\Services\TikTokScraperService;
use Hki98\LaravelTikTokScraper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TikTokScraperServiceTest extends TestCase
{
    #[Test]
    public function it_can_validate_tiktok_urls(): void
    {
        $validUrls = [
            'https://www.tiktok.com/@username/video/1234567890',
            'https://tiktok.com/@username/video/1234567890',
            'https://www.tiktok.com/@username/photo/1234567890',
            'https://www.tiktok.com/@username',
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
    public function it_validates_user_profile_input(): void
    {
        $valid = [
            'lava.rostom',
            '@lava.rostom',
            'https://www.tiktok.com/@lava.rostom',
            'https://tiktok.com/@user_name',
        ];

        foreach ($valid as $value) {
            $this->assertTrue(TikTokScraper::isValidUserInput($value), "Input should be valid: {$value}");
        }

        $invalid = [
            '',
            'not a username!!!',
            'https://www.tiktok.com/@user/video/123',
            'https://example.com/@user',
        ];

        foreach ($invalid as $value) {
            $this->assertFalse(TikTokScraper::isValidUserInput($value), "Input should be invalid: {$value}");
        }
    }

    #[Test]
    public function scrape_rejects_invalid_url_before_network(): void
    {
        $this->expectException(InvalidUrlException::class);
        TikTokScraper::scrape('https://example.com/not-tiktok');
    }

    #[Test]
    public function scrape_user_rejects_invalid_input_before_network(): void
    {
        $this->expectException(InvalidUrlException::class);
        TikTokScraper::scrapeUser('not a valid username!!!');
    }

    #[Test]
    public function it_can_clear_cache(): void
    {
        $this->expectNotToPerformAssertions();
        TikTokScraper::clearCache();
    }

    #[Test]
    public function it_can_clear_url_cache(): void
    {
        $url = 'https://www.tiktok.com/@username/video/1234567890';

        $this->expectNotToPerformAssertions();
        TikTokScraper::clearUrlCache($url);
    }

    #[Test]
    public function it_can_check_cache_existence(): void
    {
        $url = 'https://www.tiktok.com/@username/video/1234567890';

        $this->assertFalse(TikTokScraper::hasCachedResult($url));
    }

    #[Test]
    public function it_exposes_statistics(): void
    {
        $stats = TikTokScraper::getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
    }

    #[Test]
    public function facade_resolves_correctly(): void
    {
        $scraper = TikTokScraper::getFacadeRoot();

        $this->assertInstanceOf(TikTokScraperInterface::class, $scraper);
    }

    #[Test]
    public function service_is_bound_in_container(): void
    {
        $scraper = app(TikTokScraperInterface::class);

        $this->assertInstanceOf(TikTokScraperService::class, $scraper);
    }
}
