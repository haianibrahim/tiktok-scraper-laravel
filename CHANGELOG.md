# Changelog

All notable changes to `laravel-tiktok-scraper` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **BREAKING CHANGE**: Changed PHP namespace from `HaiIbrahim\LaravelTikTokScraper` to `Hki98\LaravelTikTokScraper`
- Updated all PHP class namespaces and imports throughout the package
- Maintained package name as `haianibrahim/laravel-tiktok-scraper` for Composer installation

### Migration Notes
If updating from a previous version:
1. Installation command remains the same: `composer require haianibrahim/laravel-tiktok-scraper`
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
- `POST /api/tiktok-scraper/scrape` - Scrape single video
- `POST /api/tiktok-scraper/bulk-scrape` - Bulk scraping
- `POST /api/tiktok-scraper/validate` - URL validation
- `GET /api/tiktok-scraper/stats` - Statistics
- `DELETE /api/tiktok-scraper/cache` - Clear cache
- `GET /api/tiktok-scraper/health` - Health check

### Events
- `VideoScraped` - Fired when video is successfully scraped
- `ScrapingFailed` - Fired when scraping fails
- `RateLimitHit` - Fired when rate limit is exceeded

### Exceptions
- `TikTokScraperException` - Base exception
- `InvalidUrlException` - Invalid URL provided
- `HttpException` - HTTP request failures
- `ParseException` - Data parsing failures
- `RateLimitException` - Rate limit exceeded
- `CacheException` - Cache operation failures

## [1.0.0] - 2024-01-01

### Added
- Initial package structure
- Core scraping functionality
- Laravel integration
- Documentation and examples
