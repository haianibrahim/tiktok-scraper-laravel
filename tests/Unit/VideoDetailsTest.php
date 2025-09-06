<?php

declare(strict_types=1);

namespace Hki98\LaravelTikTokScraper\Tests\Unit;

use Hki98\LaravelTikTokScraper\Data\VideoDetails;
use Hki98\LaravelTikTokScraper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class VideoDetailsTest extends TestCase
{
    #[Test]
    public function it_can_create_video_details_from_array(): void
    {
        $data = [
            'video_id' => '1234567890',
            'url' => 'https://www.tiktok.com/@username/video/1234567890',
            'title' => 'Test video title',
            'description' => 'Test video description',
            'username' => 'testuser',
            'display_name' => 'Test User',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'views' => 1000,
            'likes' => 100,
            'comments' => 10,
            'shares' => 5,
            'music_title' => 'Test Song',
            'music_author' => 'Test Artist',
            'video_url' => 'https://example.com/video.mp4',
            'cover_url' => 'https://example.com/cover.jpg',
            'duration' => 30,
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $videoDetails = VideoDetails::fromArray($data);

        $this->assertEquals('1234567890', $videoDetails->videoId);
        $this->assertEquals('https://www.tiktok.com/@username/video/1234567890', $videoDetails->url);
        $this->assertEquals('Test video title', $videoDetails->title);
        $this->assertEquals('Test video description', $videoDetails->description);
        $this->assertEquals('testuser', $videoDetails->username);
        $this->assertEquals('Test User', $videoDetails->displayName);
        $this->assertEquals('https://example.com/avatar.jpg', $videoDetails->avatarUrl);
        $this->assertEquals(1000, $videoDetails->views);
        $this->assertEquals(100, $videoDetails->likes);
        $this->assertEquals(10, $videoDetails->comments);
        $this->assertEquals(5, $videoDetails->shares);
        $this->assertEquals('Test Song', $videoDetails->musicTitle);
        $this->assertEquals('Test Artist', $videoDetails->musicAuthor);
        $this->assertEquals('https://example.com/video.mp4', $videoDetails->videoUrl);
        $this->assertEquals('https://example.com/cover.jpg', $videoDetails->coverUrl);
        $this->assertEquals(30, $videoDetails->duration);
        $this->assertEquals('2024-01-01T00:00:00Z', $videoDetails->createdAt);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $videoDetails = new VideoDetails(
            videoId: '1234567890',
            url: 'https://www.tiktok.com/@username/video/1234567890',
            title: 'Test video title',
            description: 'Test video description',
            username: 'testuser',
            displayName: 'Test User',
            avatarUrl: 'https://example.com/avatar.jpg',
            views: 1000,
            likes: 100,
            comments: 10,
            shares: 5,
            musicTitle: 'Test Song',
            musicAuthor: 'Test Artist',
            videoUrl: 'https://example.com/video.mp4',
            coverUrl: 'https://example.com/cover.jpg',
            duration: 30,
            createdAt: '2024-01-01T00:00:00Z'
        );

        $array = $videoDetails->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('1234567890', $array['video_id']);
        $this->assertEquals('https://www.tiktok.com/@username/video/1234567890', $array['url']);
        $this->assertEquals('Test video title', $array['title']);
        $this->assertEquals('testuser', $array['username']);
    }

    #[Test]
    public function it_can_convert_to_json(): void
    {
        $videoDetails = new VideoDetails(
            videoId: '1234567890',
            url: 'https://www.tiktok.com/@username/video/1234567890',
            title: 'Test video title',
            description: 'Test video description',
            username: 'testuser',
            displayName: 'Test User',
            avatarUrl: 'https://example.com/avatar.jpg',
            views: 1000,
            likes: 100,
            comments: 10,
            shares: 5,
            musicTitle: 'Test Song',
            musicAuthor: 'Test Artist',
            videoUrl: 'https://example.com/video.mp4',
            coverUrl: 'https://example.com/cover.jpg',
            duration: 30,
            createdAt: '2024-01-01T00:00:00Z'
        );

        $json = $videoDetails->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('1234567890', $decoded['video_id']);
        $this->assertEquals('testuser', $decoded['username']);
    }

    #[Test]
    public function it_calculates_engagement_rate(): void
    {
        $videoDetails = new VideoDetails(
            videoId: '1234567890',
            url: 'https://www.tiktok.com/@username/video/1234567890',
            title: 'Test video title',
            description: 'Test video description',
            username: 'testuser',
            displayName: 'Test User',
            avatarUrl: 'https://example.com/avatar.jpg',
            views: 1000,
            likes: 100,
            comments: 50,
            shares: 25,
            musicTitle: 'Test Song',
            musicAuthor: 'Test Artist',
            videoUrl: 'https://example.com/video.mp4',
            coverUrl: 'https://example.com/cover.jpg',
            duration: 30,
            createdAt: '2024-01-01T00:00:00Z'
        );

        $engagementRate = $videoDetails->getEngagementRate();

        // (100 + 50 + 25) / 1000 * 100 = 17.5%
        $this->assertEquals(17.5, $engagementRate);
    }

    #[Test]
    public function it_calculates_total_engagement(): void
    {
        $videoDetails = new VideoDetails(
            videoId: '1234567890',
            url: 'https://www.tiktok.com/@username/video/1234567890',
            title: 'Test video title',
            description: 'Test video description',
            username: 'testuser',
            displayName: 'Test User',
            avatarUrl: 'https://example.com/avatar.jpg',
            views: 1000,
            likes: 100,
            comments: 50,
            shares: 25,
            musicTitle: 'Test Song',
            musicAuthor: 'Test Artist',
            videoUrl: 'https://example.com/video.mp4',
            coverUrl: 'https://example.com/cover.jpg',
            duration: 30,
            createdAt: '2024-01-01T00:00:00Z'
        );

        $totalEngagement = $videoDetails->getTotalEngagement();

        // 100 + 50 + 25 = 175
        $this->assertEquals(175, $totalEngagement);
    }
}
