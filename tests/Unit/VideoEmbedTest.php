<?php

namespace Tests\Unit;

use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function accepted(): array
    {
        $yt = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'watch with extras' => ['https://youtube.com/watch?v=dQw4w9WgXcQ&t=30s&list=abc', $yt],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=xyz', $yt],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', $yt],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', $yt],
            'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ', $yt],
            'vimeo' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789'],
            'vimeo player' => ['https://player.vimeo.com/video/123456789?h=abc', 'https://player.vimeo.com/video/123456789'],
            'dailymotion' => ['https://www.dailymotion.com/video/x8abc12', 'https://www.dailymotion.com/embed/video/x8abc12'],
            'dailymotion short' => ['https://dai.ly/x8abc12', 'https://www.dailymotion.com/embed/video/x8abc12'],
        ];
    }

    #[DataProvider('accepted')]
    public function test_known_video_links_become_player_addresses(string $url, string $embed): void
    {
        $this->assertSame($embed, VideoEmbed::embedUrl($url));
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function refused(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'http' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'other host' => ['https://evil.example/watch?v=dQw4w9WgXcQ'],
            'lookalike host' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'credentials' => ['https://user@www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'port' => ['https://www.youtube.com:8443/watch?v=dQw4w9WgXcQ'],
            'bad id' => ['https://www.youtube.com/watch?v=short'],
            'id with quote' => ['https://www.youtube.com/watch?v=dQw4w9Wg"onload'],
            'channel page' => ['https://www.youtube.com/@someone'],
            'javascript' => ['javascript:alert(1)'],
            'vimeo not a video' => ['https://vimeo.com/channels/staffpicks'],
        ];
    }

    #[DataProvider('refused')]
    public function test_anything_else_is_refused(?string $url): void
    {
        $this->assertNull(VideoEmbed::embedUrl($url));
    }
}
