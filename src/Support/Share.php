<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Share
{
    /**
     * @return array<string,string|array{title:string,text:string,url:string}>
     */
    public static function data(string $url, string $title, string $description, string $image): array
    {
        return [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'text' => trim($title . "\n" . $description),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
            'x' => 'https://twitter.com/intent/tweet?text=' . rawurlencode($title) . '&url=' . rawurlencode($url),
            'whatsapp' => 'https://wa.me/?text=' . rawurlencode($title . "\n" . $url),
            'telegram' => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title),
            'native' => [
                'title' => $title,
                'text' => $description !== '' ? $title . ' - ' . $description : $title,
                'url' => $url,
            ],
        ];
    }

    /** @param array<string,mixed> $share */
    public static function ogHead(array $share, string $type = 'website'): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $title = $esc($share['title']);
        $desc = $esc($share['description']);
        $url = $esc($share['url']);
        $img = $esc($share['image']);

        $tags = [];
        $tags[] = '<meta property="og:type" content="' . $esc($type) . '" />';
        $tags[] = '<meta property="og:site_name" content="' . $esc(trim((string)Env::get('APP_NAME', 'Perfushopping'))) . '" />';
        $tags[] = '<meta property="og:url" content="' . $url . '" />';
        $tags[] = '<meta property="og:title" content="' . $title . '" />';
        $tags[] = '<meta property="og:description" content="' . $desc . '" />';
        if ($img !== '') {
            $tags[] = '<meta property="og:image" content="' . $img . '" />';
            $tags[] = '<meta property="og:image:alt" content="' . $title . '" />';
        }
        $tags[] = '<meta name="twitter:card" content="summary_large_image" />';
        $tags[] = '<meta name="twitter:title" content="' . $title . '" />';
        $tags[] = '<meta name="twitter:description" content="' . $desc . '" />';
        if ($img !== '') {
            $tags[] = '<meta name="twitter:image" content="' . $img . '" />';
        }
        return implode("\n    ", $tags) . "\n    ";
    }
}
