<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Groups articles about the same event.
 *
 * Each article becomes a TF-IDF vector of stemmed words (headline words count
 * double). Articles are visited oldest first. An article joins the most
 * similar existing story when all of these hold:
 *   - cosine similarity to the story's centroid ≥ threshold (the story as a
 *     whole, not its closest member: matching any one member let stories chain
 *     through shared boilerplate, e.g. "adliyeye sevk edildi");
 *   - it shares at least one headline word with the story's headlines and
 *     at least `minShared` words overall;
 *   - the story had an article within the last `maxGap` seconds;
 *   - the two don't name different places ("İstanbul'da kaza" vs "Ankara'da kaza",
 *     "Başakşehir'de okul" vs "Fransa'da okul"; see Text::places).
 * Otherwise it starts a new story.
 */
final class Clusterer
{
    /**
     * @param list<array{id:int,source_id:string,title:string,summary:string,published_at:int}> $articles
     * @return list<array{members:list<int>, rep:int, sources:list<string>, first_seen:int, last_seen:int}>
     */
    public static function cluster(array $articles, float $threshold, int $minShared, int $maxGapSeconds): array
    {
        if (!$articles) {
            return [];
        }
        usort($articles, fn ($a, $b) => [$a['published_at'], $a['id']] <=> [$b['published_at'], $b['id']]);

        // 1. Term frequencies.
        $tf = [];
        $titleSets = [];
        $placeSets = [];
        $df = [];
        foreach ($articles as $i => $a) {
            $t = Text::stems($a['title']);
            $s = array_slice(Text::stems($a['summary']), 0, 40);
            $vec = [];
            foreach ($t as $w) {
                $vec[$w] = ($vec[$w] ?? 0) + 2.0;
            }
            foreach ($s as $w) {
                $vec[$w] = ($vec[$w] ?? 0) + 1.0;
            }
            $tf[$i] = $vec;
            $titleSets[$i] = array_fill_keys($t, true);
            $placeSets[$i] = Text::places($a['title']);
            foreach ($vec as $w => $_) {
                $df[$w] = ($df[$w] ?? 0) + 1;
            }
        }

        // 2. TF-IDF, L2-normalised.
        $n = count($articles);
        $vecs = [];
        foreach ($tf as $i => $vec) {
            $norm = 0.0;
            foreach ($vec as $w => $f) {
                $vec[$w] = (1 + log($f)) * log(($n + 1) / ($df[$w] + 0.5));
                $norm += $vec[$w] ** 2;
            }
            $norm = sqrt($norm);
            if ($norm > 0) {
                foreach ($vec as $w => $v) {
                    $vec[$w] = $v / $norm;
                }
            }
            $vecs[$i] = $vec;
        }

        // 3. Greedy single pass. An inverted index over story centroids keeps
        //    this fast for thousands of articles.
        $centroid = [];   // story => [term => weight sum]
        $cnorm2 = [];     // story => squared norm of centroid
        $titles = [];     // story => [term => true]   (headline words)
        $words = [];      // story => [term => true]   (all words)
        $places = [];     // story => [place => true]
        $last = [];       // story => newest published_at
        $members = [];    // story => list of article indexes
        $cIndex = [];     // term => [story => true]

        foreach ($articles as $i => $a) {
            $vec = $vecs[$i];

            $cDots = [];
            foreach ($vec as $w => $v) {
                foreach ($cIndex[$w] ?? [] as $c => $_) {
                    $cDots[$c] = ($cDots[$c] ?? 0.0) + $v * $centroid[$c][$w];
                }
            }
            $sims = [];
            foreach ($cDots as $c => $dot) {
                $sims[$c] = $cnorm2[$c] > 0 ? $dot / sqrt($cnorm2[$c]) : 0.0;
            }
            arsort($sims);

            $best = null;
            foreach ($sims as $c => $sim) {
                if ($sim < $threshold) {
                    break;
                }
                if ($a['published_at'] - $last[$c] > $maxGapSeconds) {
                    continue;
                }
                if ($placeSets[$i] && $places[$c] && !array_intersect_key($placeSets[$i], $places[$c])) {
                    continue;
                }
                if (!array_intersect_key($titleSets[$i], $titles[$c])) {
                    continue;
                }
                if (count(array_intersect_key($vec, $words[$c])) < $minShared) {
                    continue;
                }
                $best = $c;
                break;
            }

            if ($best === null) {
                $best = count($members);
                $centroid[$best] = [];
                $cnorm2[$best] = 0.0;
                $titles[$best] = [];
                $words[$best] = [];
                $places[$best] = [];
                $last[$best] = $a['published_at'];
                $members[$best] = [];
            }

            foreach ($vec as $w => $v) {
                $old = $centroid[$best][$w] ?? 0.0;
                $centroid[$best][$w] = $old + $v;
                $cnorm2[$best] += ($old + $v) ** 2 - $old ** 2;
                $cIndex[$w][$best] = true;
                $words[$best][$w] = true;
            }
            $titles[$best] += $titleSets[$i];
            $places[$best] += $placeSets[$i];
            $last[$best] = max($last[$best], $a['published_at']);
            $members[$best][] = $i;
        }

        // 4. Representative headline: the article closest to the story's centroid.
        $out = [];
        foreach ($members as $c => $idx) {
            $repI = $idx[0];
            $repSim = -1.0;
            foreach ($idx as $i) {
                $dot = 0.0;
                foreach ($vecs[$i] as $w => $v) {
                    $dot += $v * $centroid[$c][$w];
                }
                if ($dot > $repSim + 1e-9) {
                    $repSim = $dot;
                    $repI = $i;
                }
            }
            $out[] = [
                'members' => array_map(fn ($i) => $articles[$i]['id'], $idx),
                'rep' => $articles[$repI]['id'],
                'sources' => array_values(array_unique(array_map(fn ($i) => $articles[$i]['source_id'], $idx))),
                'first_seen' => $articles[$idx[0]]['published_at'],
                'last_seen' => $last[$c],
            ];
        }
        return $out;
    }
}
