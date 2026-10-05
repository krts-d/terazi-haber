<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Parses RSS 2.0, RSS 1.0 (RDF) and Atom into a flat list of items.
 */
final class FeedParser
{
    private const NS_MEDIA   = 'http://search.yahoo.com/mrss/';
    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';
    private const NS_DC      = 'http://purl.org/dc/elements/1.1/';
    private const NS_ATOM    = 'http://www.w3.org/2005/Atom';
    private const NS_RSS1    = 'http://purl.org/rss/1.0/';

    /**
     * @return list<array{guid:string,url:string,title:string,summary:string,image:?string,published_at:int}>
     */
    public static function parse(string $raw, string $baseUrl, ?int $now = null): array
    {
        $now ??= time();
        $xml = self::load($raw);
        $root = strtolower($xml->getName());

        $items = [];
        if ($root === 'rss') {
            foreach ($xml->channel->item ?? [] as $it) {
                $items[] = self::rssItem($it, $baseUrl, $now);
            }
        } elseif ($root === 'feed') {
            $atom = $xml->children(self::NS_ATOM);
            $entries = count($atom->entry) ? $atom->entry : $xml->entry;
            foreach ($entries ?? [] as $e) {
                $items[] = self::atomEntry($e, $baseUrl, $now);
            }
        } elseif ($root === 'rdf') {
            $rss1 = $xml->children(self::NS_RSS1);
            foreach ($rss1->item ?? [] as $it) {
                $items[] = self::rssItem($it, $baseUrl, $now, self::NS_RSS1);
            }
        } else {
            throw new \RuntimeException("Not a feed (root element <{$xml->getName()}>)");
        }

        return array_values(array_filter($items, fn ($i) => $i !== null));
    }

    private static function load(string $raw): \SimpleXMLElement
    {
        $raw = ltrim($raw, "\xEF\xBB\xBF \t\r\n");
        if ($raw === '' || $raw[0] !== '<') {
            throw new \RuntimeException('Response is not XML');
        }
        // Convert non-UTF-8 feeds (e.g. ISO-8859-9 / windows-1254) to UTF-8.
        if (preg_match('/^<\?xml[^>]*encoding=["\']([^"\']+)["\']/i', $raw, $m)) {
            $enc = strtoupper($m[1]);
            if ($enc !== 'UTF-8' && $enc !== 'UTF8') {
                $converted = @mb_convert_encoding($raw, 'UTF-8', $enc === 'WINDOWS-1254' ? 'Windows-1254' : $enc);
                if ($converted !== false) {
                    $raw = preg_replace('/(^<\?xml[^>]*encoding=["\'])[^"\']+/i', '${1}UTF-8', $converted, 1);
                }
            }
        }
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8'); // drops invalid sequences
        }
        // Strip characters that are illegal in XML 1.0 (common in hand-rolled feeds).
        $raw = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $raw) ?? $raw;

        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET | LIBXML_COMPACT);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($xml === false) {
            $msg = $errors ? trim($errors[0]->message) : 'unknown error';
            throw new \RuntimeException("Invalid XML: $msg");
        }
        return $xml;
    }

    private static function rssItem(\SimpleXMLElement $it, string $base, int $now, ?string $ns = null): ?array
    {
        $c = $ns ? $it->children($ns) : $it;
        $title = Text::clean((string)$c->title);
        $link = trim((string)$c->link);
        if ($link === '' && isset($it->guid) && ((self::attrs($it->guid)['isPermaLink'] ?? '') !== 'false')) {
            $link = trim((string)$it->guid);
        }
        if ($link === '') {
            $about = $it->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $link = trim((string)($about['about'] ?? ''));
        }
        $url = self::absUrl($link, $base);
        if ($title === '' || $url === null) {
            return null;
        }

        $content = $it->children(self::NS_CONTENT);
        $dc = $it->children(self::NS_DC);
        $descHtml = (string)$c->description;
        $encoded = (string)($content->encoded ?? '');

        $date = (string)($c->pubDate ?? '') ?: (string)($dc->date ?? '');
        $guid = trim((string)($it->guid ?? '')) ?: $url;

        return [
            'guid' => mb_substr($guid, 0, 500),
            'url' => $url,
            'title' => mb_substr($title, 0, 400),
            'summary' => self::summary($descHtml !== '' ? $descHtml : $encoded, $title),
            'image' => self::image($it, $base, $descHtml . ' ' . $encoded),
            'published_at' => self::date($date, $now),
        ];
    }

    private static function atomEntry(\SimpleXMLElement $e, string $base, int $now): ?array
    {
        $a = count($e->children(self::NS_ATOM)) ? $e->children(self::NS_ATOM) : $e;
        $title = Text::clean((string)$a->title);
        $link = '';
        $image = null;
        foreach ($a->link as $l) {
            $at = self::attrs($l);
            $rel = $at['rel'] ?? 'alternate';
            if ($rel === 'alternate' && $link === '') {
                $link = $at['href'] ?? '';
            } elseif ($rel === 'enclosure' && str_starts_with($at['type'] ?? '', 'image/')) {
                $image = self::absUrl($at['href'] ?? '', $base);
            }
        }
        $url = self::absUrl($link, $base);
        if ($title === '' || $url === null) {
            return null;
        }
        $html = (string)$a->summary ?: (string)$a->content;
        return [
            'guid' => mb_substr(trim((string)$a->id) ?: $url, 0, 500),
            'url' => $url,
            'title' => mb_substr($title, 0, 400),
            'summary' => self::summary($html, $title),
            'image' => $image ?? self::image($e, $base, (string)$a->content . ' ' . (string)$a->summary),
            'published_at' => self::date((string)$a->published ?: (string)$a->updated, $now),
        ];
    }

    private static function image(\SimpleXMLElement $it, string $base, string $html): ?string
    {
        $media = $it->children(self::NS_MEDIA);
        $candidates = [];
        foreach ([$media->content, $media->group->content ?? null] as $set) {
            foreach ($set ?? [] as $m) {
                $at = self::attrs($m);
                $type = $at['type'] ?? '';
                $medium = $at['medium'] ?? '';
                $u = $at['url'] ?? '';
                if ($medium === 'image' || str_starts_with($type, 'image/') || self::looksLikeImage($u)) {
                    $candidates[] = $u;
                }
            }
        }
        foreach ($media->thumbnail ?? [] as $t) {
            $candidates[] = self::attrs($t)['url'] ?? '';
        }
        foreach ($it->enclosure ?? [] as $enc) {
            $at = self::attrs($enc);
            $u = $at['url'] ?? '';
            if (str_starts_with($at['type'] ?? '', 'image/') || self::looksLikeImage($u)) {
                $candidates[] = $u;
            }
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            $candidates[] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        foreach ($candidates as $c) {
            $abs = self::absUrl(trim($c), $base);
            if ($abs !== null) {
                return mb_substr($abs, 0, 1000);
            }
        }
        return null;
    }

    /**
     * Un-prefixed attributes. ($el['x'] silently returns nothing on elements
     * reached through ->children($namespace), e.g. media:content or Atom links.)
     * @return array<string,string>
     */
    private static function attrs(\SimpleXMLElement $el): array
    {
        $out = [];
        foreach ($el->attributes() ?? [] as $k => $v) {
            $out[$k] = (string)$v;
        }
        return $out;
    }

    private static function looksLikeImage(string $u): bool
    {
        return (bool)preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|$)/i', $u);
    }

    private static function summary(string $html, string $title): string
    {
        $text = Text::clean($html);
        if ($text === '' || Text::fold($text) === Text::fold($title)) {
            return '';
        }
        // Many feeds start the description with the headline again.
        if (str_starts_with(Text::fold($text), Text::fold($title))) {
            $text = ltrim(mb_substr($text, mb_strlen($title)), " .:-–—");
        }
        if (mb_strlen($text) > 280) {
            $cut = mb_substr($text, 0, 280);
            $sp = mb_strrpos($cut, ' ');
            $text = rtrim(mb_substr($cut, 0, $sp ?: 280), " ,.;:-") . '…';
        }
        return $text;
    }

    public static function absUrl(string $u, string $base): ?string
    {
        $u = trim($u);
        if ($u === '') {
            return null;
        }
        if (str_starts_with($u, '//')) {
            $u = 'https:' . $u;
        } elseif (str_starts_with($u, '/')) {
            $u = rtrim($base, '/') . $u;
        }
        $scheme = strtolower((string)parse_url($u, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || !parse_url($u, PHP_URL_HOST)) {
            return null;
        }
        return $u;
    }

    private const TR_DATE = [
        'Ocak' => 'January', 'Şubat' => 'February', 'Mart' => 'March', 'Nisan' => 'April',
        'Mayıs' => 'May', 'Haziran' => 'June', 'Temmuz' => 'July', 'Ağustos' => 'August',
        'Eylül' => 'September', 'Ekim' => 'October', 'Kasım' => 'November', 'Aralık' => 'December',
        'Pazartesi' => 'Monday', 'Salı' => 'Tuesday', 'Çarşamba' => 'Wednesday', 'Perşembe' => 'Thursday',
        'Cuma' => 'Friday', 'Cumartesi' => 'Saturday', 'Pazar' => 'Sunday',
        'Pzt' => 'Mon', 'Sal' => 'Tue', 'Çar' => 'Wed', 'Per' => 'Thu', 'Cum' => 'Fri', 'Cmt' => 'Sat', 'Paz' => 'Sun',
        'Oca' => 'Jan', 'Şub' => 'Feb', 'Nis' => 'Apr', 'Haz' => 'Jun', 'Tem' => 'Jul',
        'Ağu' => 'Aug', 'Eyl' => 'Sep', 'Eki' => 'Oct', 'Kas' => 'Nov', 'Ara' => 'Dec',
    ];

    public static function date(string $s, int $now): int
    {
        $s = trim($s);
        if ($s === '') {
            return $now;
        }
        $ts = strtotime($s);
        if ($ts === false) {
            // Longest names first so "Cumartesi" is not mangled by "Cum".
            $map = self::TR_DATE;
            uksort($map, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $ts = strtotime(strtr($s, $map));
        }
        if ($ts === false || $ts > $now + 600) {
            return $now; // unparseable or in the future: treat as "just now"
        }
        return $ts;
    }
}
