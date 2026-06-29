# Changelog

All notable changes to `tiktok-scraper-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-06-29

### Added
- **Standalone Package Implementation**: Removed composer dependency on `haianibrahim/tiktok-scraper`. Ported all core scraping, HTTP requesting, embedded JSON parsing, and normalization logic directly into the package.
- **Photo post scraping** - `scrape()` now fully supports TikTok photo (slideshow) posts, returning a `VideoDetails` object.
- **User profile scraping** - New `scrapeUser(string $usernameOrUrl)` method returning a `UserInfo` object. Accepts a bare username, `@username`, or a full profile URL.
- New `UserInfo` data object with helpers (`getProfileUrl()`, `getFormattedFollowers()`, `getFormattedHearts()`).
- New `isValidUserInput()`, `getCachedUserDetails()`, and `hasCachedResult()` methods on the service/facade.
- New `UserScraped` event dispatched after a successful profile scrape.
- New API endpoint `POST /api/tiktok-scraper/user` to scrape a user profile.
- `VideoDetails::getTotalEngagement()` and `getUserProfileUrl()` helpers.
- Dedicated `phpunit.xml` plus expanded Unit and Feature test coverage for video, photo, and user flows.

### Changed
- **Now implemented as a standalone package** instead of maintaining a separate scraper dependency.
- Updated dependencies: requires `guzzlehttp/guzzle ^7.8`.
- `VideoDetails` now mirrors the native data shape (`canonicalUrl`, `videoId`, `description`, `userNickname`, `username`, `userId`, `thumbnail`, `views`, `likes`, `comments`, `shares`, `favorites`).
- `clearCache()` and `clearUrlCache()` now return `void`.
- **BREAKING CHANGE**: Changed PHP namespace from `HaiIbrahim\LaravelTikTokScraper` to `Hki98\LaravelTikTokScraper`
- Updated all PHP class namespaces and imports throughout the package
- Maintained package name as `haianibrahim/tiktok-scraper-laravel` for Composer installation

### Migration Notes
If updating from a previous version:
1. Installation command remains the same: `composer require haianibrahim/tiktok-scraper-laravel`
2. Update any direct class imports in your code from `HaiIbrahim\LaravelTikTokScraper\*` to `Hki98\LaravelTikTokScraper\*`
3. Re-publish configuration files: `php artisan vendor:publish --provider="Hki98\LaravelTikTokScraper\TikTokScraperServiceProvider" --tag="config" --force`

### Added
- Initial release of Laravel TikTok Scraper package
- Laravel 12.x.x compatibility
- Comprehensive TikTok video scraping functionality
- Service provider with auto-discovery
- Configuration file with extensive options
- Caching support with multiple drivers
- Rate limiting with middleware
- Event system for monitoring scraping operations
- Exception handling with specific exception types
- HTTP API endpoints for REST access
- Artisan commands for CLI management
- Database migration for logging
- Facade for easy static access
- Full test suite with PHPUnit
- Comprehensive documentation

### Features
- Scrape single TikTok videos
- Bulk scraping support
- URL validation
- Cache management (clear all, clear by URL)
- Statistics tracking and reporting
- Health check endpoint
- Background processing ready
- Multiple output formats (JSON, CSV)
- User-agent customization
- Request timeout configuration
- Error logging and monitoring

### Commands
- `tiktok-scraper:test` - Test scraper functionality
- `tiktok-scraper:bulk` - Bulk scrape from file
- `tiktok-scraper:stats` - View and manage statistics
- `tiktok-scraper:clear-cache` - Cache management

### API Endpoints
- `POST /api/tiktok-scraper/scrape` - Scrape single video or photo post
- `POST /api/tiktok-scraper/user` - Scrape user profile
- `POST /api/tiktok-scraper/bulk-scrape` - Bulk scraping
- `POST /api/tiktok-scraper/validate` - URL validation
- `GET /api/tiktok-scraper/stats` - Statistics
- `DELETE /api/tiktok-scraper/cache` - Clear cache
- `GET /api/tiktok-scraper/health` - Health check

### Events
- `VideoScraped` - Fired when a video or photo post is successfully scraped
- `UserScraped` - Fired when a user profile is successfully scraped
- `ScrapingFailed` - Fired when scraping fails
- `RateLimitHit` - Fired when rate limit is exceeded

### Exceptions
- `TikTokScraperException` - Base exception
- `InvalidUrlException` - Invalid URL or user input provided
- `HttpRequestException` - HTTP request failures
- `EmptyResponseException` - Empty responses from TikTok
- `ParseException` - Data parsing failures
- `RateLimitException` - Rate limit exceeded

## [1.0.0] - 2024-01-01

### Added
- Initial package structure
- Core scraping functionality
- Laravel integration
- Documentation and examples
