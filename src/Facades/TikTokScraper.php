<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Facades;

use Hki98\LaravelTikTokScraper\Contracts\TikTokScraperInterface;
use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Illuminate\Support\Facades\Facade;

/**
 * @method static VideoDetails scrape(string $url, bool $useCache = true)
 * @method static array scrapeMultiple(array $urls, bool $useCache = true)
 * @method static bool isValidTikTokUrl(string $url)
 * @method static VideoDetails|null getCachedDetails(string $url)
 * @method static bool clearCache(?string $url = null)
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
