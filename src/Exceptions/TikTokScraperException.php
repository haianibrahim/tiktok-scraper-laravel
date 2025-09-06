<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Exceptions;

use Exception;

/**
 * Base exception for all TikTok scraper-related errors.
 */
class TikTokScraperException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the exception context for logging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
