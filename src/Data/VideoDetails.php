<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Data Transfer Object for TikTok video details.
 */
final class VideoDetails implements Arrayable, Jsonable, JsonSerializable
{
    public function __construct(
        public readonly string $canonicalUrl,
        public readonly string $videoId,
        public readonly string $description,
        public readonly string $userNickname,
        public readonly string $username,
        public readonly string $userId,
        public readonly string $thumbnail,
        public readonly int $views,
        public readonly int $likes,
        public readonly int $comments,
        public readonly int $shares,
        public readonly int $favorites,
        public readonly array $rawData = [],
        public readonly ?string $scrapedAt = null,
    ) {
    }

    /**
     * Create VideoDetails from the original TikTok scraper data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            canonicalUrl: $data['link'] ?? $data['canonical'] ?? '',
            videoId: $data['video_id'] ?? $data['videoId'] ?? '',
            description: $data['video_desc'] ?? $data['description'] ?? '',
            userNickname: $data['user'] ?? $data['userNickname'] ?? '',
            username: $data['username'] ?? '',
            userId: $data['user_id'] ?? $data['userId'] ?? '',
            thumbnail: $data['thumbnail'] ?? '',
            views: (int) ($data['views'] ?? 0),
            likes: (int) ($data['likes'] ?? 0),
            comments: (int) ($data['comments'] ?? 0),
            shares: (int) ($data['shares'] ?? 0),
            favorites: (int) ($data['favorites'] ?? 0),
            rawData: $data,
            scrapedAt: now()->toISOString(),
        );
    }

    /**
     * Convert to array for backwards compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => 'ok',
            'canonical_url' => $this->canonicalUrl,
            'link' => $this->canonicalUrl, // Backwards compatibility
            'video_id' => $this->videoId,
            'video_desc' => $this->description,
            'description' => $this->description, // Additional alias
            'user' => $this->userNickname,
            'user_nickname' => $this->userNickname, // Additional alias
            'username' => $this->username,
            'user_id' => $this->userId,
            'thumbnail' => $this->thumbnail,
            'views' => $this->views,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'shares' => $this->shares,
            'favorites' => $this->favorites,
            'scraped_at' => $this->scrapedAt,
        ];
    }

    /**
     * Convert to JSON string.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the video URL for embedding.
     *
     * @return string
     */
    public function getEmbedUrl(): string
    {
        return "https://www.tiktok.com/embed/v2/{$this->videoId}";
    }

    /**
     * Get the user profile URL.
     *
     * @return string
     */
    public function getUserProfileUrl(): string
    {
        return "https://www.tiktok.com/@{$this->username}";
    }

    /**
     * Check if the video has high engagement.
     *
     * @param int $threshold
     * @return bool
     */
    public function hasHighEngagement(int $threshold = 10000): bool
    {
        return ($this->likes + $this->comments + $this->shares) >= $threshold;
    }

    /**
     * Get engagement rate as a percentage.
     *
     * @return float
     */
    public function getEngagementRate(): float
    {
        if ($this->views === 0) {
            return 0.0;
        }

        $totalEngagement = $this->likes + $this->comments + $this->shares;
        return ($totalEngagement / $this->views) * 100;
    }

    /**
     * Format view count in a human-readable way.
     *
     * @return string
     */
    public function getFormattedViews(): string
    {
        return $this->formatNumber($this->views);
    }

    /**
     * Format like count in a human-readable way.
     *
     * @return string
     */
    public function getFormattedLikes(): string
    {
        return $this->formatNumber($this->likes);
    }

    /**
     * Format number in a human-readable way (K, M, B).
     *
     * @param int $number
     * @return string
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        }
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }
}
