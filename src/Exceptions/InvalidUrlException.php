<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Exceptions;

/**
 * Thrown when an invalid TikTok URL is provided.
 */
class InvalidUrlException extends TikTokScraperException
{
    public static function forUrl(string $url): self
    {
        return new self("Invalid TikTok URL provided: {$url}");
    }
}
