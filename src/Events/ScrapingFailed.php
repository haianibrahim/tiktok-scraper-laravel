<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Events;

use Hki98\LaravelTikTokScraper\Exceptions\TikTokScraperException;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScrapingFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $url,
        public readonly TikTokScraperException $exception
    ) {
    }
}
