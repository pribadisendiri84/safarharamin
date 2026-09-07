<?php

namespace App\Support;

class YoutubeUrl
{
    public static function extractId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:embed/|shorts/|live/|watch\?v=|watch\?.*&v=))([A-Za-z0-9_-]{11})~', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function embedUrl(?string $url, int $startSeconds = 0): ?string
    {
        $id = self::extractId($url);
        if ($id === null) {
            return null;
        }

        $query = 'rel=0&modestbranding=1';
        if ($startSeconds > 0) {
            $query .= '&start='.$startSeconds;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$id.'?'.$query;
    }

    public static function thumbnailUrl(?string $url): ?string
    {
        $id = self::extractId($url);

        return $id ? 'https://img.youtube.com/vi/'.$id.'/hqdefault.jpg' : null;
    }
}
