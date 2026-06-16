<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests\Unit;

use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class VideoDetailsTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            'status' => 'ok',
            'link' => 'https://www.tiktok.com/@testuser/video/1234567890',
            'user' => 'Test User',
            'username' => 'testuser',
            'user_id' => '999888777',
            'video_id' => '1234567890',
            'video_desc' => 'Test video description',
            'thumbnail' => 'https://example.com/cover.jpg',
            'views' => 1000,
            'likes' => 100,
            'comments' => 50,
            'shares' => 25,
            'favorites' => 10,
        ];
    }

    #[Test]
    public function it_can_create_video_details_from_array(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        $this->assertEquals('1234567890', $videoDetails->videoId);
        $this->assertEquals('https://www.tiktok.com/@testuser/video/1234567890', $videoDetails->canonicalUrl);
        $this->assertEquals('Test video description', $videoDetails->description);
        $this->assertEquals('testuser', $videoDetails->username);
        $this->assertEquals('Test User', $videoDetails->userNickname);
        $this->assertEquals('999888777', $videoDetails->userId);
        $this->assertEquals('https://example.com/cover.jpg', $videoDetails->thumbnail);
        $this->assertEquals(1000, $videoDetails->views);
        $this->assertEquals(100, $videoDetails->likes);
        $this->assertEquals(50, $videoDetails->comments);
        $this->assertEquals(25, $videoDetails->shares);
        $this->assertEquals(10, $videoDetails->favorites);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        $array = $videoDetails->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('ok', $array['status']);
        $this->assertEquals('1234567890', $array['video_id']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('https://www.tiktok.com/@testuser/video/1234567890', $array['link']);
    }

    #[Test]
    public function it_can_convert_to_json(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        $json = $videoDetails->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('1234567890', $decoded['video_id']);
        $this->assertEquals('testuser', $decoded['username']);
    }

    #[Test]
    public function it_calculates_total_engagement(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        // 100 + 50 + 25 = 175
        $this->assertEquals(175, $videoDetails->getTotalEngagement());
    }

    #[Test]
    public function it_calculates_engagement_rate(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        // (100 + 50 + 25) / 1000 * 100 = 17.5%
        $this->assertEquals(17.5, $videoDetails->getEngagementRate());
    }

    #[Test]
    public function it_builds_user_profile_url(): void
    {
        $videoDetails = VideoDetails::fromArray($this->sampleData());

        $this->assertEquals('https://www.tiktok.com/@testuser', $videoDetails->getUserProfileUrl());
    }

    #[Test]
    public function it_formats_numbers_in_human_readable_way(): void
    {
        $data = $this->sampleData();
        $data['views'] = 1500000;
        $videoDetails = VideoDetails::fromArray($data);

        $this->assertEquals('1.5M', $videoDetails->getFormattedViews());
    }
}
