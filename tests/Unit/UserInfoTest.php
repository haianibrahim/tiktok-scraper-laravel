<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests\Unit;

use Hki98\LaravelTikTokScraper\Data\UserInfo;
use Hki98\LaravelTikTokScraper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserInfoTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'status' => 'ok',
            'user_id' => '7284074948746216480',
            'sec_uid' => 'MS4wLjABAAAA-secuid',
            'username' => 'testuser',
            'nickname' => 'Test User',
            'signature' => 'Just a bio',
            'avatar_thumb' => 'https://example.com/thumb.jpg',
            'avatar_medium' => 'https://example.com/medium.jpg',
            'avatar_larger' => 'https://example.com/larger.jpg',
            'verified' => false,
            'private_account' => false,
            'create_time' => 1696200000,
            'region' => 'US',
            'follower_count' => 1500,
            'following_count' => 10,
            'heart_count' => 25000,
            'video_count' => 42,
            'digg_count' => 100,
            'friend_count' => 5,
            'profile_url' => 'https://www.tiktok.com/@testuser',
            'share_title' => 'Test User on TikTok',
            'share_desc' => 'Follow Test User',
        ];
    }

    #[Test]
    public function it_can_create_user_info_from_array(): void
    {
        $info = UserInfo::fromArray($this->sampleData());

        $this->assertEquals('7284074948746216480', $info->userId);
        $this->assertEquals('MS4wLjABAAAA-secuid', $info->secUid);
        $this->assertEquals('testuser', $info->username);
        $this->assertEquals('Test User', $info->nickname);
        $this->assertEquals('Just a bio', $info->signature);
        $this->assertFalse($info->verified);
        $this->assertFalse($info->privateAccount);
        $this->assertEquals(1696200000, $info->createTime);
        $this->assertEquals('US', $info->region);
        $this->assertEquals(1500, $info->followerCount);
        $this->assertEquals(25000, $info->heartCount);
        $this->assertEquals(42, $info->videoCount);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $info = UserInfo::fromArray($this->sampleData());

        $array = $info->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('ok', $array['status']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals(1500, $array['follower_count']);
        $this->assertArrayHasKey('scraped_at', $array);
    }

    #[Test]
    public function it_can_convert_to_json(): void
    {
        $info = UserInfo::fromArray($this->sampleData());

        $decoded = json_decode($info->toJson(), true);

        $this->assertEquals('testuser', $decoded['username']);
        $this->assertEquals('7284074948746216480', $decoded['user_id']);
    }

    #[Test]
    public function it_returns_profile_url(): void
    {
        $info = UserInfo::fromArray($this->sampleData());

        $this->assertEquals('https://www.tiktok.com/@testuser', $info->getProfileUrl());
    }

    #[Test]
    public function it_formats_follower_and_heart_counts(): void
    {
        $info = UserInfo::fromArray($this->sampleData());

        $this->assertEquals('1.5K', $info->getFormattedFollowers());
        $this->assertEquals('25K', $info->getFormattedHearts());
    }
}
