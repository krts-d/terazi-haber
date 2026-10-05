<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Turkish-aware text helpers used for cleaning feed text and for grouping
 * articles about the same event.
 */
final class Text
{
    /** Decode entities (twice: many feeds double-encode), strip tags, collapse whitespace. */
    public static function clean(string $s): string
    {
        for ($i = 0; $i < 2 && str_contains($s, '&'); $i++) {
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $s = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $s) ?? $s;
        $s = preg_replace('/<br\s*\/?>|<\/p>/i', ' ', $s) ?? $s;
        $s = strip_tags($s);
        $s = preg_replace('/[\s\x{00A0}\x{200B}]+/u', ' ', $s) ?? $s;
        return trim($s);
    }

    /** Turkish lowercase, then fold diacritics so "İmamoğlu" and "imamoglu" match. */
    public static function fold(string $s): string
    {
        $s = strtr($s, ['I' => 'ı', 'İ' => 'i']);
        $s = mb_strtolower($s, 'UTF-8');
        return strtr($s, [
            'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c',
            'â' => 'a', 'î' => 'i', 'û' => 'u', "\u{0307}" => '',
        ]);
    }

    /**
     * Words → crude stems.
     * Turkish is agglutinative, so ordinary words are cut to their first 5
     * letters ("seçimlerde", "seçimin" → "secim"), a well-known, surprisingly
     * effective stemming shortcut for Turkish search.
     * Proper nouns are kept whole so "Bayram" and "Bayraktar" stay apart.
     * A word counts as a proper noun when it carries an apostrophe suffix
     * ("Ankara'da" → "ankara") or is capitalised mid-sentence.
     *
     * @return list<string>
     */
    public static function stems(string $s): array
    {
        $s = preg_replace('/(\d)[.,](\d)/u', '$1d$2', $s) ?? $s;  // keep "5,1" (magnitude, rates) as one token
        preg_match_all("/[\p{L}\p{N}]+(?:['’‘`ʼ][\p{L}\p{N}]+)*|[:.!?|]/u", $s, $m);
        $tokens = $m[0];

        // Headlines In Title Case or ALL CAPS: capitalisation says nothing, so only trust apostrophes.
        $words = array_filter($tokens, fn ($t) => mb_strlen($t) > 3);
        $caps = array_filter($words, fn ($t) => self::startsUpper($t));
        $trustCaps = !$words || count($caps) / count($words) < 0.6;

        $out = [];
        $sentenceStart = true;
        foreach ($tokens as $tok) {
            if (preg_match('/^[:.!?|]$/', $tok)) {
                $sentenceStart = true;
                continue;
            }
            $parts = preg_split("/['’‘`ʼ]/u", $tok);
            $root = $parts[0];
            $proper = count($parts) > 1 || ($trustCaps && !$sentenceStart && self::startsUpper($root));
            $sentenceStart = false;

            $w = self::fold($root);
            $isNum = (bool)preg_match('/^\d+(d\d+)?$/', $w);
            if ($isNum ? strlen($w) < 2 : mb_strlen($w) < 3) {
                continue;
            }
            if (isset(self::STOP[$w])) {
                continue;
            }
            if ($isNum) {
                $out[] = $w;
                continue;
            }
            $stem = mb_substr($w, 0, 5);
            if (isset(self::STOP_STEMS[$stem])) {
                continue;
            }
            $out[] = $proper ? mb_substr($w, 0, 12) : $stem;
        }
        return $out;
    }

    private static function startsUpper(string $w): bool
    {
        $c = mb_substr($w, 0, 1);
        return $c !== mb_strtolower($c) && $c === mb_strtoupper($c);
    }

    /**
     * Turkish provinces named in a headline. Two headlines that name different
     * provinces and none in common ("İstanbul'da kaza" / "Ankara'da kaza") are
     * almost always different events, so the clusterer keeps them apart.
     *
     * @return array<string, true>
     */
    public static function places(string $s): array
    {
        $s = self::fold($s);
        $s = preg_replace("/['’‘`ʼ]\p{L}+/u", '', $s) ?? $s;
        $out = [];
        foreach (preg_split('/[^\p{L}]+/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $w) {
            if (isset(self::PROVINCES[$w])) {
                $out[self::PROVINCES[$w]] = true;
            }
        }
        return $out;
    }

    private const PROVINCES = [
        'adana' => 'adana', 'adiyaman' => 'adiyaman', 'afyon' => 'afyon', 'afyonkarahisar' => 'afyon', 'agri' => 'agri',
        'aksaray' => 'aksaray', 'amasya' => 'amasya', 'ankara' => 'ankara', 'antalya' => 'antalya', 'ardahan' => 'ardahan',
        'artvin' => 'artvin', 'aydin' => 'aydin', 'balikesir' => 'balikesir', 'bartin' => 'bartin', 'batman' => 'batman',
        'bayburt' => 'bayburt', 'bilecik' => 'bilecik', 'bingol' => 'bingol', 'bitlis' => 'bitlis', 'bolu' => 'bolu',
        'burdur' => 'burdur', 'bursa' => 'bursa', 'canakkale' => 'canakkale', 'cankiri' => 'cankiri', 'corum' => 'corum',
        'denizli' => 'denizli', 'diyarbakir' => 'diyarbakir', 'duzce' => 'duzce', 'edirne' => 'edirne', 'elazig' => 'elazig',
        'erzincan' => 'erzincan', 'erzurum' => 'erzurum', 'eskisehir' => 'eskisehir', 'gaziantep' => 'gaziantep', 'antep' => 'gaziantep',
        'giresun' => 'giresun', 'gumushane' => 'gumushane', 'hakkari' => 'hakkari', 'hatay' => 'hatay', 'igdir' => 'igdir',
        'isparta' => 'isparta', 'istanbul' => 'istanbul', 'izmir' => 'izmir', 'kahramanmaras' => 'kahramanmaras', 'maras' => 'kahramanmaras',
        'karabuk' => 'karabuk', 'karaman' => 'karaman', 'kars' => 'kars', 'kastamonu' => 'kastamonu', 'kayseri' => 'kayseri',
        'kilis' => 'kilis', 'kirikkale' => 'kirikkale', 'kirklareli' => 'kirklareli', 'kirsehir' => 'kirsehir', 'kocaeli' => 'kocaeli',
        'konya' => 'konya', 'kutahya' => 'kutahya', 'malatya' => 'malatya', 'manisa' => 'manisa', 'mardin' => 'mardin',
        'mersin' => 'mersin', 'mugla' => 'mugla', 'mus' => 'mus', 'nevsehir' => 'nevsehir', 'nigde' => 'nigde',
        'ordu' => 'ordu', 'osmaniye' => 'osmaniye', 'rize' => 'rize', 'sakarya' => 'sakarya', 'samsun' => 'samsun',
        'sanliurfa' => 'sanliurfa', 'urfa' => 'sanliurfa', 'siirt' => 'siirt', 'sinop' => 'sinop', 'sirnak' => 'sirnak',
        'sivas' => 'sivas', 'tekirdag' => 'tekirdag', 'tokat' => 'tokat', 'trabzon' => 'trabzon', 'tunceli' => 'tunceli',
        'usak' => 'usak', 'van' => 'van', 'yalova' => 'yalova', 'yozgat' => 'yozgat', 'zonguldak' => 'zonguldak',
    ];

    /** Stems of generic news verbs ("açıklandı", "sürüyor", "devam ediyor"...) that say nothing about the event. */
    private const STOP_STEMS = [
        'acikl' => 1, 'devam' => 1, 'ediyo' => 1, 'edild' => 1, 'edile' => 1, 'yapil' => 1, 'yapti' => 1,
        'yapac' => 1, 'basla' => 1, 'surdu' => 1, 'suruy' => 1, 'konus' => 1, 'soyle' => 1, 'ifade' => 1,
        'belir' => 1, 'kaldi' => 1, 'geldi' => 1, 'cikti' => 1, 'aldi' => 1, 'verdi' => 1, 'veril' => 1,
        'alind' => 1, 'oldug' => 1, 'olaca' => 1, 'olmas' => 1, 'gerek' => 1, 'ilisk' => 1, 'sonra' => 1,
        // Titles and stock phrases that appear in every kind of story.
        'baska' => 1, 'bakan' => 1, 'cumhu' => 1, 'prof' => 1, 'hayat' => 1, 'kaybe' => 1, 'ugurl' => 1,
        'ziyar' => 1, 'goste' => 1, 'dikka' => 1, 'cekti' => 1, 'ceken' => 1, 'yenid' => 1, 'birbi' => 1,
        'girdi' => 1, 'suphe' => 1, 'yakal' => 1, 'yasin' => 1, 'degis' => 1, 'feci' => 1, 'yaral' => 1,
        'meyda' => 1, 'tespi' => 1, 'ortay' => 1, 'duyur' => 1, 'tepki' => 1, 'iddia' => 1, 'mesaj' => 1,
        'uyard' => 1, 'uyari' => 1, 'kriti' => 1, 'carpi' => 1, 'dakik' => 1, 'gelis' => 1, 'sert' => 1,
    ];

    /** Stopwords, already folded (no Turkish diacritics). */
    private const STOP = [
        've' => 1, 'ile' => 1, 'bir' => 1, 'bu' => 1, 'su' => 1, 'icin' => 1, 'gibi' => 1, 'olarak' => 1,
        'cok' => 1, 'daha' => 1, 'her' => 1, 'ama' => 1, 'fakat' => 1, 'veya' => 1, 'yada' => 1,
        'sonra' => 1, 'once' => 1, 'kadar' => 1, 'son' => 1, 'dakika' => 1, 'sondakika' => 1,
        'haber' => 1, 'haberi' => 1, 'haberler' => 1, 'haberleri' => 1, 'aciklama' => 1, 'acikladi' => 1,
        'aciklamasi' => 1, 'yeni' => 1, 'oldu' => 1, 'olan' => 1, 'olacak' => 1, 'oldugu' => 1, 'olmak' => 1,
        'olur' => 1, 'etti' => 1, 'eden' => 1, 'edildi' => 1, 'dedi' => 1, 'diye' => 1, 'ise' => 1,
        'ancak' => 1, 'hem' => 1, 'uzere' => 1, 'karsi' => 1, 'tum' => 1, 'butun' => 1, 'bile' => 1,
        'cunku' => 1, 'gore' => 1, 'var' => 1, 'yok' => 1, 'ilk' => 1, 'iki' => 1, 'kez' => 1,
        'bugun' => 1, 'dun' => 1, 'yarin' => 1, 'flas' => 1, 'canli' => 1, 'video' => 1, 'foto' => 1,
        'galeri' => 1, 'izle' => 1, 'iste' => 1, 'nedir' => 1, 'neden' => 1, 'nasil' => 1, 'kim' => 1,
        'kimdir' => 1, 'hangi' => 1, 'ozel' => 1, 'ilgili' => 1, 'artik' => 1, 'hala' => 1,
        'tekrar' => 1, 'yine' => 1, 'yapti' => 1, 'yapildi' => 1, 'verdi' => 1, 'aldi' => 1,
        'geldi' => 1, 'bas' => 1, 'icinde' => 1, 'uzerine' => 1, 'arasinda' => 1, 'den' => 1,
        'dan' => 1, 'nin' => 1, 'nun' => 1, 'mi' => 1, 'mu' => 1, 'da' => 1, 'de' => 1, 'ki' => 1,
        'ne' => 1, 'en' => 1, 'ya' => 1, 'iliskin' => 1, 'dair' => 1, 'sey' => 1,
        'olay' => 1, 'iddia' => 1, 'konustu' => 1, 'soyledi' => 1, 'belirtti' => 1, 'ifade' => 1,
        'yaptigi' => 1, 'buyuk' => 1, 'onemli' => 1, 'gelisme' => 1, 'gundem' => 1, 'turkiye' => 1,
        'the' => 1, 'and' => 1, 'for' => 1, 'with' => 1,
    ];
}
