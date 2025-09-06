<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Exceptions;

/**
 * Thrown when rate limit is exceeded.
 */
class RateLimitException extends TikTokScraperException
{
    public static function exceeded(int $maxAttempts, int $decaySeconds): self
    {
        return new self(
            "Rate limit exceeded. Maximum {$maxAttempts} attempts allowed every {$decaySeconds} seconds."
        );
    }
}
