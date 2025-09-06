<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Hki98\LaravelTikTokScraper\TikTokScraperServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TikTokScraperServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'TikTokScraper' => \Hki98\LaravelTikTokScraper\Facades\TikTokScraper::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('tiktok-scraper', [
            'http_client' => [
                'timeout' => 30,
                'connect_timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'headers' => [
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                ],
            ],
            'cache' => [
                'enabled' => true,
                'store' => 'array',
                'ttl' => 3600,
                'prefix' => 'tiktok_scraper',
            ],
            'rate_limiting' => [
                'enabled' => true,
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
        ]);
    }
}
