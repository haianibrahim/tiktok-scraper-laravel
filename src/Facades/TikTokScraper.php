<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Facades;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Data\UserInfo;
use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Illuminate\Support\Facades\Facade;

/**
 * @method static VideoDetails scrape(string $url, bool $useCache = true)
 * @method static UserInfo scrapeUser(string $usernameOrUrl, bool $useCache = true)
 * @method static array scrapeMultiple(array $urls, bool $useCache = true)
 * @method static bool isValidTikTokUrl(string $url)
 * @method static bool isValidUserInput(string $value)
 * @method static VideoDetails|null getCachedDetails(string $url)
 * @method static UserInfo|null getCachedUserDetails(string $usernameOrUrl)
 * @method static bool hasCachedResult(string $url)
 * @method static void clearCache()
 * @method static void clearUrlCache(string $url)
 * @method static array getStatistics()
 *
 * @see TikTokScraperInterface
 */
class TikTokScraper extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return TikTokScraperInterface::class;
    }
}
