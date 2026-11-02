<?php

namespace App\Support;

/**
 * A promotional video link (YouTube, Vimeo or Dailymotion) as the address of its embedded player.
 * Only these hosts are accepted, and the player address is rebuilt from the video id, so nothing
 * the instructor typed is ever put in a page's iframe as it is.
 */
class VideoEmbed
{
    public static function embedUrl(?string $url): ?string
    {
        $parts = parse_url(trim((string) $url));

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;
        $path = (string) ($parts['path'] ?? '');
        parse_str((string) ($parts['query'] ?? ''), $query);

        // YouTube: watch?v=ID, youtu.be/ID, /shorts/ID, /embed/ID, /live/ID
        if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true) || $host === 'youtu.be') {
            $id = $host === 'youtu.be'
                ? ltrim($path, '/')
                : (preg_match('#^/(?:shorts|embed|live|v)/([^/]+)#', $path, $m) ? $m[1] : (string) ($query['v'] ?? ''));

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
        }

        if ($host === 'vimeo.com' || $host === 'player.vimeo.com') {
            return preg_match('#^/(?:video/)?(\d{5,12})(?:/|$)#', $path, $m) ? 'https://player.vimeo.com/video/'.$m[1] : null;
        }

        if ($host === 'dailymotion.com' || $host === 'dai.ly') {
            $id = $host === 'dai.ly'
                ? ltrim($path, '/')
                : (preg_match('#^/(?:embed/)?video/([a-z0-9]+)#i', $path, $m) ? $m[1] : '');

            return preg_match('/^[a-z0-9]{5,12}$/i', $id) ? 'https://www.dailymotion.com/embed/video/'.$id : null;
        }

        return null;
    }
}
