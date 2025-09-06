<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper;

use GuzzleHttp\Client;
use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Services\TikTokScraperService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TikTokScraperServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public array $bindings = [
        TikTokScraperInterface::class => TikTokScraperService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tiktok-scraper.php',
            'tiktok-scraper'
        );

        $this->registerHttpClient();
        $this->registerTikTokScraperService();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfiguration();
        $this->publishMigrations();
        $this->publishViews();
        $this->loadCommands();
        $this->loadRoutes();
        $this->registerEventListeners();
    }

    /**
     * Register the HTTP client for TikTok scraper.
     */
    protected function registerHttpClient(): void
    {
        $this->app->singleton('tiktok-scraper.http-client', function (Application $app) {
            $config = $app['config']['tiktok-scraper.http'];

            return new Client([
                'timeout' => $config['timeout'] ?? 30,
                'connect_timeout' => $config['connect_timeout'] ?? 10,
                'headers' => [
                    'User-Agent' => $config['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ],
            ]);
        });
    }

    /**
     * Register the main TikTok scraper service.
     */
    protected function registerTikTokScraperService(): void
    {
        $this->app->singleton(TikTokScraperInterface::class, function (Application $app) {
            return new TikTokScraperService(
                httpClient: $app['tiktok-scraper.http-client'],
                cache: $app[CacheManager::class],
                config: $app['config']['tiktok-scraper']
            );
        });

        $this->app->alias(TikTokScraperInterface::class, 'tiktok-scraper');
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tiktok-scraper.php' => config_path('tiktok-scraper.php'),
        ], 'tiktok-scraper-config');
    }

    /**
     * Publish migrations.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'tiktok-scraper-migrations');
    }

    /**
     * Publish views.
     */
    protected function publishViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tiktok-scraper');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tiktok-scraper'),
        ], 'tiktok-scraper-views');
    }

    /**
     * Load Artisan commands.
     */
    protected function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TestScraperCommand::class,
                Commands\ClearCacheCommand::class,
                Commands\BulkScrapeCommand::class,
            ]);
        }
    }

    /**
     * Load package routes.
     */
    protected function loadRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Event listeners will be registered here
        // This allows users to listen to scraping events
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            TikTokScraperInterface::class,
            'tiktok-scraper',
            'tiktok-scraper.http-client',
        ];
    }
}
