<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Exceptions;

/**
 * Thrown when response body is empty.
 */
class EmptyResponseException extends TikTokScraperException
{
    public static function create(): self
    {
        return new self('Empty response body from TikTok.');
    }
}
