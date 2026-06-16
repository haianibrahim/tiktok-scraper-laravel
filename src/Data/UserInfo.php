<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * Data Transfer Object for TikTok user/profile details.
 */
final class UserInfo implements Arrayable, Jsonable, JsonSerializable
{
    public function __construct(
        public readonly string $userId,
        public readonly string $secUid,
        public readonly string $username,
        public readonly string $nickname,
        public readonly string $signature,
        public readonly string $avatarThumb,
        public readonly string $avatarMedium,
        public readonly string $avatarLarger,
        public readonly bool $verified,
        public readonly bool $privateAccount,
        public readonly int $createTime,
        public readonly string $region,
        public readonly int $followerCount,
        public readonly int $followingCount,
        public readonly int $heartCount,
        public readonly int $videoCount,
        public readonly int $diggCount,
        public readonly int $friendCount,
        public readonly string $profileUrl,
        public readonly string $shareTitle,
        public readonly string $shareDesc,
        public readonly ?string $scrapedAt = null,
    ) {
    }

    /**
     * Create UserInfo from the native scraper array output.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (string) ($data['user_id'] ?? $data['userId'] ?? ''),
            secUid: (string) ($data['sec_uid'] ?? $data['secUid'] ?? ''),
            username: (string) ($data['username'] ?? ''),
            nickname: (string) ($data['nickname'] ?? ''),
            signature: (string) ($data['signature'] ?? ''),
            avatarThumb: (string) ($data['avatar_thumb'] ?? $data['avatarThumb'] ?? ''),
            avatarMedium: (string) ($data['avatar_medium'] ?? $data['avatarMedium'] ?? ''),
            avatarLarger: (string) ($data['avatar_larger'] ?? $data['avatarLarger'] ?? ''),
            verified: (bool) ($data['verified'] ?? false),
            privateAccount: (bool) ($data['private_account'] ?? $data['privateAccount'] ?? false),
            createTime: (int) ($data['create_time'] ?? $data['createTime'] ?? 0),
            region: (string) ($data['region'] ?? ''),
            followerCount: (int) ($data['follower_count'] ?? $data['followerCount'] ?? 0),
            followingCount: (int) ($data['following_count'] ?? $data['followingCount'] ?? 0),
            heartCount: (int) ($data['heart_count'] ?? $data['heartCount'] ?? 0),
            videoCount: (int) ($data['video_count'] ?? $data['videoCount'] ?? 0),
            diggCount: (int) ($data['digg_count'] ?? $data['diggCount'] ?? 0),
            friendCount: (int) ($data['friend_count'] ?? $data['friendCount'] ?? 0),
            profileUrl: (string) ($data['profile_url'] ?? $data['profileUrl'] ?? ''),
            shareTitle: (string) ($data['share_title'] ?? $data['shareTitle'] ?? ''),
            shareDesc: (string) ($data['share_desc'] ?? $data['shareDesc'] ?? ''),
            scrapedAt: now()->toISOString(),
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => 'ok',
            'user_id' => $this->userId,
            'sec_uid' => $this->secUid,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'signature' => $this->signature,
            'avatar_thumb' => $this->avatarThumb,
            'avatar_medium' => $this->avatarMedium,
            'avatar_larger' => $this->avatarLarger,
            'verified' => $this->verified,
            'private_account' => $this->privateAccount,
            'create_time' => $this->createTime,
            'region' => $this->region,
            'follower_count' => $this->followerCount,
            'following_count' => $this->followingCount,
            'heart_count' => $this->heartCount,
            'video_count' => $this->videoCount,
            'digg_count' => $this->diggCount,
            'friend_count' => $this->friendCount,
            'profile_url' => $this->profileUrl,
            'share_title' => $this->shareTitle,
            'share_desc' => $this->shareDesc,
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
     * Get the canonical profile URL.
     *
     * @return string
     */
    public function getProfileUrl(): string
    {
        return $this->profileUrl !== ''
            ? $this->profileUrl
            : "https://www.tiktok.com/@{$this->username}";
    }

    /**
     * Format follower count in a human-readable way.
     *
     * @return string
     */
    public function getFormattedFollowers(): string
    {
        return $this->formatNumber($this->followerCount);
    }

    /**
     * Format heart (likes) count in a human-readable way.
     *
     * @return string
     */
    public function getFormattedHearts(): string
    {
        return $this->formatNumber($this->heartCount);
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
