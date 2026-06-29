# Laravel TikTok Scraper

A Laravel package that provides an easy way to scrape TikTok video, photo post, and user profile data directly into your Laravel application. This package is a standalone scraper implementation that adds Laravel-specific features including caching, rate limiting, events, and comprehensive API endpoints.

## Features

- 🚀 **Laravel 11, 12 & 13 Compatible** - Built for modern Laravel versions
- 🎬 **Video Scraping** - Extract metadata and engagement stats from video URLs
- 🖼️ **Photo Post Scraping** - Full support for TikTok photo (slideshow) posts
- 👤 **User Profile Scraping** - Fetch profile details from a username or profile URL
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
composer require haianibrahim/tiktok-scraper-laravel
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

echo $videoDetails->description;
echo $videoDetails->username;
echo $videoDetails->views;

// Scrape a photo (slideshow) post - same method, same VideoDetails object
$photoPost = TikTokScraper::scrape('https://www.tiktok.com/@username/photo/1234567890');

echo $photoPost->userNickname;
echo $photoPost->likes;

// Scrape a user profile (accepts a bare username, @username, or profile URL)
$user = TikTokScraper::scrapeUser('scout2015');

echo $user->nickname;
echo $user->getFormattedFollowers(); // e.g. "9M"
echo $user->verified ? 'Verified' : 'Not verified';

// Scrape multiple videos
$videos = TikTokScraper::scrapeMultiple([
    'https://www.tiktok.com/@user1/video/1234567890',
    'https://www.tiktok.com/@user2/video/0987654321',
]);

// Validate input before scraping
if (TikTokScraper::isValidTikTokUrl($url)) {
    $videoDetails = TikTokScraper::scrape($url);
}

if (TikTokScraper::isValidUserInput($username)) {
    $user = TikTokScraper::scrapeUser($username);
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

The `VideoDetails` object (returned for both video and photo posts) contains the following information:

```php
$videoDetails = TikTokScraper::scrape($url);

// Basic information
echo $videoDetails->videoId;
echo $videoDetails->description;
echo $videoDetails->canonicalUrl;
echo $videoDetails->thumbnail;

// User information
echo $videoDetails->username;
echo $videoDetails->userNickname;
echo $videoDetails->userId;
echo $videoDetails->getUserProfileUrl(); // https://www.tiktok.com/@username

// Statistics
echo $videoDetails->views;
echo $videoDetails->likes;
echo $videoDetails->comments;
echo $videoDetails->shares;
echo $videoDetails->favorites;

// Calculated metrics & helpers
echo $videoDetails->getEngagementRate();   // Percentage of views
echo $videoDetails->getTotalEngagement();  // likes + comments + shares + favorites
echo $videoDetails->getFormattedViews();   // e.g. "1.5M"
echo $videoDetails->getFormattedLikes();   // e.g. "35.1K"
echo $videoDetails->getEmbedUrl();

// Convert to array/JSON
$array = $videoDetails->toArray();
$json = $videoDetails->toJson();
```

### User Info Object

The `UserInfo` object is returned by `scrapeUser()`:

```php
$user = TikTokScraper::scrapeUser('scout2015');

// Identity
echo $user->userId;
echo $user->secUid;
echo $user->username;
echo $user->nickname;
echo $user->signature;
echo $user->region;

// Avatars
echo $user->avatarThumb;
echo $user->avatarMedium;
echo $user->avatarLarger;

// Flags
echo $user->verified ? 'verified' : 'not verified';
echo $user->privateAccount ? 'private' : 'public';

// Counts
echo $user->followerCount;
echo $user->followingCount;
echo $user->heartCount;
echo $user->videoCount;

// Helpers
echo $user->getProfileUrl();        // https://www.tiktok.com/@username
echo $user->getFormattedFollowers(); // e.g. "9M"
echo $user->getFormattedHearts();    // e.g. "108.2M"

// Convert to array/JSON
$array = $user->toArray();
$json = $user->toJson();
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

### Scrape User Profile

```http
POST /api/tiktok-scraper/user
Content-Type: application/json

{
    "user": "scout2015",
    "use_cache": true
}
```

The `user` field accepts a bare username, an `@username`, or a full profile URL.

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

### UserScraped Event

```php
use Hki98\LaravelTikTokScraper\Events\UserScraped;

Event::listen(UserScraped::class, function (UserScraped $event) {
    Log::info('User scraped successfully', [
        'input' => $event->input,
        'user_id' => $event->userInfo->userId,
        'username' => $event->userInfo->username,
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
    ]);
});
```

## Exception Handling

The package provides specific exceptions for different error scenarios:

```php
use Hki98\LaravelTikTokScraper\Exceptions\{
    TikTokScraperException,
    InvalidUrlException,
    HttpRequestException,
    EmptyResponseException,
    ParseException,
    RateLimitException
};

try {
    $videoDetails = TikTokScraper::scrape($url);
} catch (InvalidUrlException $e) {
    // Handle invalid URL or user input
} catch (RateLimitException $e) {
    // Handle rate limiting
} catch (HttpRequestException $e) {
    // Handle HTTP errors
} catch (EmptyResponseException $e) {
    // Handle empty responses
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
- Laravel 11.0, 12.0, or 13.0
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
