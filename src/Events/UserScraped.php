<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Events;

use Hki98\LaravelTikTokScraper\Data\UserInfo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserScraped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $input,
        public readonly UserInfo $userInfo
    ) {
    }
}
