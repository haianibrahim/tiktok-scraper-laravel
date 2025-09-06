# Laravel TikTok Scraper

A Laravel package that provides an easy way to scrape TikTok video data directly into your Laravel application. This package wraps the [haianibrahim/tiktok-scraper](https://github.com/haianibrahim/tiktok-scraper) with Laravel-specific features including caching, rate limiting, events, and comprehensive API endpoints.

## Features

- 🚀 **Laravel 12.x.x Compatible** - Built for the latest Laravel version
- 📦 **Easy Integration** - Simple service provider and facade
- 🔧 **Configurable** - Comprehensive configuration options
- 💾 **Caching Support** - Built-in caching with multiple drivers
- ⚡ **Rate Limiting** - Prevent API abuse with customizable rate limits
- 📊 **Statistics Tracking** - Monitor scraping performance and usage
- 🎯 **Event System** - Laravel events for scraping operations
- 🛡️ **Exception Handling** - Comprehensive error handling
- 🌐 **HTTP API** - Ready-to-use REST API endpoints
- 🧪 **Testing Support** - Full test suite included
- 📝 **Artisan Commands** - CLI tools for testing and management

## Installation

Install the package via Composer:

```bash
composer require haianibrahim/laravel-tiktok-scraper
```

### Laravel Auto-Discovery

The package uses Laravel's auto-discovery feature, so the service provider will be automatically registered.

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Hki98\LaravelTikTokScraper\TikTokScraperServiceProvider" --tag="config"
```

### Run Migrations

Publish and run the database migrations for logging:

```bash
php artisan vendor:publish --provider="Hki98\LaravelTikTokScraper\TikTokScraperServiceProvider" --tag="migrations"
php artisan migrate
```

## Configuration

The configuration file is published to `config/tiktok-scraper.php`. Key configuration options include:

```php
return [
    // HTTP Client Configuration
    'http_client' => [
        'timeout' => env('TIKTOK_SCRAPER_TIMEOUT', 30),
        'connect_timeout' => env('TIKTOK_SCRAPER_CONNECT_TIMEOUT', 10),
        'user_agent' => env('TIKTOK_SCRAPER_USER_AGENT', 'Mozilla/5.0...'),
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
        ],
    ],

    // Caching Configuration
    'cache' => [
        'enabled' => env('TIKTOK_SCRAPER_CACHE_ENABLED', true),
        'store' => env('TIKTOK_SCRAPER_CACHE_STORE', null),
        'ttl' => env('TIKTOK_SCRAPER_CACHE_TTL', 3600),
        'prefix' => env('TIKTOK_SCRAPER_CACHE_PREFIX', 'tiktok_scraper'),
    ],

    // Rate Limiting Configuration
    'rate_limiting' => [
        'enabled' => env('TIKTOK_SCRAPER_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('TIKTOK_SCRAPER_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_minutes' => env('TIKTOK_SCRAPER_RATE_LIMIT_DECAY_MINUTES', 1),
    ],
];
```

## Usage

### Using the Facade

```php
use Hki98\LaravelTikTokScraper\Facades\TikTokScraper;

// Scrape a single video
$videoDetails = TikTokScraper::scrape('https://www.tiktok.com/@username/video/1234567890');

echo $videoDetails->title;
echo $videoDetails->username;
echo $videoDetails->views;

// Scrape multiple videos
$videos = TikTokScraper::scrapeMultiple([
    'https://www.tiktok.com/@user1/video/1234567890',
    'https://www.tiktok.com/@user2/video/0987654321',
]);

// Validate URL
if (TikTokScraper::isValidTikTokUrl($url)) {
    $videoDetails = TikTokScraper::scrape($url);
}

// Cache management
TikTokScraper::clearCache();
TikTokScraper::clearUrlCache($url);
```

### Using Dependency Injection

```php
use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;

class VideoController extends Controller
{
    public function __construct(
        private readonly TikTokScraperInterface $scraper
    ) {}

    public function scrape(Request $request)
    {
        $url = $request->input('url');
        
        try {
            $videoDetails = $this->scraper->scrape($url);
            return response()->json($videoDetails->toArray());
        } catch (TikTokScraperException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### Video Details Object

The `VideoDetails` object contains comprehensive video information:

```php
$videoDetails = TikTokScraper::scrape($url);

// Basic video information
echo $videoDetails->videoId;
echo $videoDetails->title;
echo $videoDetails->description;
echo $videoDetails->url;

// User information
echo $videoDetails->username;
echo $videoDetails->displayName;
echo $videoDetails->avatarUrl;

// Statistics
echo $videoDetails->views;
echo $videoDetails->likes;
echo $videoDetails->comments;
echo $videoDetails->shares;

// Media information
echo $videoDetails->videoUrl;
echo $videoDetails->coverUrl;
echo $videoDetails->duration;

// Music information
echo $videoDetails->musicTitle;
echo $videoDetails->musicAuthor;

// Calculated metrics
echo $videoDetails->getEngagementRate(); // Percentage
echo $videoDetails->getTotalEngagement(); // Total likes + comments + shares

// Convert to array/JSON
$array = $videoDetails->toArray();
$json = $videoDetails->toJson();
```

## Artisan Commands

### Test Scraper

Test the scraper functionality:

```bash
php artisan tiktok-scraper:test https://www.tiktok.com/@username/video/1234567890
```

### Bulk Scraping

Scrape multiple URLs from a file:

```bash
# From file with JSON output
php artisan tiktok-scraper:bulk urls.txt --output=results.json

# From file with CSV output
php artisan tiktok-scraper:bulk urls.txt --output=results.csv --format=csv
```

### View Statistics

View scraping statistics:

```bash
# Show stats
php artisan tiktok-scraper:stats

# Clear stats
php artisan tiktok-scraper:stats --clear
```

### Cache Management

Manage the cache:

```bash
# Clear all cache
php artisan tiktok-scraper:clear-cache

# Clear specific cache
php artisan tiktok-scraper:clear-cache --url="https://www.tiktok.com/@username/video/1234567890"

# Clear with confirmation
php artisan tiktok-scraper:clear-cache --force
```

## HTTP API Endpoints

The package provides ready-to-use API endpoints:

### Scrape Single Video

```http
POST /api/tiktok-scraper/scrape
Content-Type: application/json

{
    "url": "https://www.tiktok.com/@username/video/1234567890",
    "use_cache": true
}
```

### Bulk Scrape

```http
POST /api/tiktok-scraper/bulk-scrape
Content-Type: application/json

{
    "urls": [
        "https://www.tiktok.com/@user1/video/1234567890",
        "https://www.tiktok.com/@user2/video/0987654321"
    ],
    "use_cache": true
}
```

### Validate URL

```http
POST /api/tiktok-scraper/validate
Content-Type: application/json

{
    "url": "https://www.tiktok.com/@username/video/1234567890"
}
```

### Get Statistics

```http
GET /api/tiktok-scraper/stats
```

### Clear Cache

```http
DELETE /api/tiktok-scraper/cache
```

### Health Check

```http
GET /api/tiktok-scraper/health
```

## Events

The package dispatches Laravel events for monitoring:

### VideoScraped Event

```php
use Hki98\LaravelTikTokScraper\Events\VideoScraped;

// Listen for successful scrapes
Event::listen(VideoScraped::class, function (VideoScraped $event) {
    Log::info('Video scraped successfully', [
        'url' => $event->url,
        'video_id' => $event->videoDetails->videoId,
        'username' => $event->videoDetails->username,
    ]);
});
```

### ScrapingFailed Event

```php
use Hki98\LaravelTikTokScraper\Events\ScrapingFailed;

Event::listen(ScrapingFailed::class, function (ScrapingFailed $event) {
    Log::error('Scraping failed', [
        'url' => $event->url,
        'error' => $event->exception->getMessage(),
    ]);
});
```

### RateLimitHit Event

```php
use Hki98\LaravelTikTokScraper\Events\RateLimitHit;

Event::listen(RateLimitHit::class, function (RateLimitHit $event) {
    Log::warning('Rate limit hit', [
        'url' => $event->url,
        'retry_after' => $event->retryAfter,
    ]);
});
```

## Exception Handling

The package provides specific exceptions for different error scenarios:

```php
use Hki98\LaravelTikTokScraper\Exceptions\{
    TikTokScraperException,
    InvalidUrlException,
    HttpException,
    ParseException,
    RateLimitException,
    CacheException
};

try {
    $videoDetails = TikTokScraper::scrape($url);
} catch (InvalidUrlException $e) {
    // Handle invalid URL
} catch (RateLimitException $e) {
    // Handle rate limiting
} catch (HttpException $e) {
    // Handle HTTP errors
} catch (ParseException $e) {
    // Handle parsing errors
} catch (TikTokScraperException $e) {
    // Handle general scraper errors
}
```

## Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0
- GuzzleHTTP 7.8 or higher

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you find this package helpful, please consider starring the repository.

For issues and feature requests, please use the [GitHub issue tracker](https://github.com/haianibrahim/laravel-tiktok-scraper/issues).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail via the contact information in the composer.json file. All security vulnerabilities will be promptly addressed.

## Credits

- [Haian Ibrahim](https://github.com/haianibrahim)
- Based on the original [tiktok-scraper](https://github.com/haianibrahim/tiktok-scraper) package
- All Contributors
