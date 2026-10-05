<?php
declare(strict_types=1);

namespace Terazi;

final class Store
{
    public function __construct(private \PDO $db)
    {
    }

    // ── Writing (used by fetch.php) ─────────────────────────────────

    /** @return array<string, array<string,mixed>> */
    public function feedState(): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM feed_state') as $r) {
            $out[$r['source_id']] = $r;
        }
        return $out;
    }

    public function saveFeedState(string $id, int $status, ?string $error, ?int $items, ?string $etag, ?string $lastModified): void
    {
        $st = $this->db->prepare(<<<SQL
            INSERT INTO feed_state (source_id, etag, last_modified, last_fetch, last_status, last_error, last_items)
            VALUES (:id, :etag, :lm, :t, :st, :err, :items)
            ON CONFLICT(source_id) DO UPDATE SET
                etag = COALESCE(:etag, etag),
                last_modified = COALESCE(:lm, last_modified),
                last_fetch = :t, last_status = :st, last_error = :err,
                last_items = COALESCE(:items, last_items)
        SQL);
        $st->execute([':id' => $id, ':etag' => $etag, ':lm' => $lastModified, ':t' => time(),
            ':st' => $status, ':err' => $error, ':items' => $items]);
    }

    /** @param list<array<string,mixed>> $items @return int number of new articles */
    public function insertArticles(string $sourceId, array $items): int
    {
        $st = $this->db->prepare(<<<SQL
            INSERT OR IGNORE INTO articles (source_id, guid, url, title, summary, image, published_at, fetched_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        SQL);
        $new = 0;
        $now = time();
        $this->db->beginTransaction();
        foreach ($items as $it) {
            $st->execute([$sourceId, $it['guid'], $it['url'], $it['title'], $it['summary'], $it['image'], $it['published_at'], $now]);
            $new += $st->rowCount();
        }
        $this->db->commit();
        return $new;
    }

    public function prune(int $keepDays): int
    {
        $cut = time() - $keepDays * 86400;
        $st = $this->db->prepare('DELETE FROM articles WHERE published_at < ?');
        $st->execute([$cut]);
        return $st->rowCount();
    }

    /** Regroup recent articles into stories. Returns [stories, multi-source stories]. */
    public function rebuildStories(array $cfg): array
    {
        $since = time() - (int)$cfg['cluster_window_hours'] * 3600;
        $sourceIds = array_keys(App::sources());
        if (!$sourceIds) {
            return [0, 0];
        }
        $in = implode(',', array_fill(0, count($sourceIds), '?'));
        $st = $this->db->prepare("SELECT id, source_id, title, summary, published_at FROM articles WHERE published_at >= ? AND source_id IN ($in)");
        $st->execute([$since, ...$sourceIds]);
        $articles = array_map(fn ($r) => ['id' => (int)$r['id'], 'published_at' => (int)$r['published_at']] + $r, $st->fetchAll());

        $clusters = Clusterer::cluster(
            $articles,
            (float)$cfg['cluster_threshold'],
            (int)$cfg['cluster_min_shared'],
            (int)$cfg['cluster_max_gap_hours'] * 3600,
        );

        $now = time();
        $this->db->beginTransaction();
        $this->db->exec('DELETE FROM story_articles');
        $this->db->exec('DELETE FROM stories');
        $ins = $this->db->prepare('INSERT INTO stories (id, rep_article, n_sources, n_articles, first_seen, last_seen, score) VALUES (?,?,?,?,?,?,?)');
        $link = $this->db->prepare('INSERT INTO story_articles (story_id, article_id) VALUES (?, ?)');
        $multi = 0;
        foreach ($clusters as $c) {
            $id = min($c['members']);
            $ns = count($c['sources']);
            $ageH = max(0, $now - $c['last_seen']) / 3600;
            $score = ($ns ** 1.2) * exp(-$ageH / 18) + 0.05 * count($c['members']);
            $ins->execute([$id, $c['rep'], $ns, count($c['members']), $c['first_seen'], $c['last_seen'], $score]);
            foreach ($c['members'] as $aid) {
                $link->execute([$id, $aid]);
            }
            if ($ns > 1) {
                $multi++;
            }
        }
        $this->db->prepare('INSERT INTO meta (key, value) VALUES (\'updated_at\', ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([(string)$now]);
        $this->db->commit();
        return [count($clusters), $multi];
    }

    // ── Reading (used by the pages) ─────────────────────────────────

    public function updatedAt(): ?int
    {
        $v = $this->db->query("SELECT value FROM meta WHERE key = 'updated_at'")->fetchColumn();
        return $v === false ? null : (int)$v;
    }

    /**
     * Stories with all their articles attached, best first.
     * @return list<array<string,mixed>>
     */
    public function stories(int $minSources, int $limit, int $offset = 0): array
    {
        $st = $this->db->prepare('SELECT * FROM stories WHERE n_sources >= ? ORDER BY score DESC, last_seen DESC LIMIT ? OFFSET ?');
        $st->execute([$minSources, $limit, $offset]);
        return $this->hydrate($st->fetchAll());
    }

    /** Newest stories that only one outlet has reported. */
    public function briefs(int $limit): array
    {
        $st = $this->db->prepare('SELECT * FROM stories WHERE n_sources = 1 ORDER BY last_seen DESC LIMIT ?');
        $st->execute([$limit]);
        return $this->hydrate($st->fetchAll());
    }

    /** Stories that only one side is reporting. */
    public function blindspots(int $minSources, int $limit): array
    {
        $st = $this->db->prepare('SELECT * FROM stories WHERE n_sources >= ? ORDER BY score DESC LIMIT 200');
        $st->execute([$minSources]);
        $out = [];
        foreach ($this->hydrate($st->fetchAll()) as $s) {
            $side = self::blindSide($s['counts']);
            if ($side !== null) {
                $s['blind_side'] = $side;   // the side that IS covering it
                $out[] = $s;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Newest articles from enabled outlets (optionally one side only), newest
     * first. Each row gets 'story': its hydrated story when two or more
     * outlets have covered it, otherwise null.
     * @return list<array<string,mixed>>
     */
    public function latest(int $limit, int $offset = 0, ?string $side = null): array
    {
        $sourceIds = $this->sourceIds($side);
        if (!$sourceIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($sourceIds), '?'));
        $st = $this->db->prepare(<<<SQL
            SELECT a.*, sa.story_id FROM articles a
            LEFT JOIN story_articles sa ON sa.article_id = a.id
            WHERE a.source_id IN ($in)
            ORDER BY a.published_at DESC, a.id DESC LIMIT ? OFFSET ?
        SQL);
        $st->execute([...$sourceIds, $limit, $offset]);
        return $this->withStories($st->fetchAll());
    }

    /** @return list<string> enabled outlet ids, optionally one side only */
    private function sourceIds(?string $side): array
    {
        return array_keys(array_filter(App::sources(), fn ($s) => $side === null || $s['label'] === $side));
    }

    /** Give each article row 'story': its hydrated story if 2+ outlets covered it, else null. */
    private function withStories(array $rows): array
    {
        $storyIds = array_values(array_unique(array_filter(array_map(fn ($r) => (int)$r['story_id'], $rows))));
        $stories = [];
        if ($storyIds) {
            $in = implode(',', array_fill(0, count($storyIds), '?'));
            $st = $this->db->prepare("SELECT * FROM stories WHERE n_sources > 1 AND id IN ($in)");
            $st->execute($storyIds);
            foreach ($this->hydrate($st->fetchAll()) as $s) {
                $stories[(int)$s['id']] = $s;
            }
        }
        foreach ($rows as &$r) {
            $r['story'] = $stories[(int)$r['story_id']] ?? null;
        }
        return $rows;
    }

    /** The side that is alone in covering a story (needs 2+ of its outlets), or null. */
    public static function blindSide(array $c): ?string
    {
        if ($c['gov'] === 0 && $c['opp'] >= 2) {
            return 'opp';
        }
        if ($c['opp'] === 0 && $c['gov'] >= 2) {
            return 'gov';
        }
        return null;
    }

    /**
     * Stories with an article published in [$from, $to), most outlets first.
     * @return list<array<string,mixed>>
     */
    public function storiesBetween(int $from, int $to): array
    {
        $st = $this->db->prepare('SELECT * FROM stories WHERE last_seen >= ? AND first_seen < ? ORDER BY n_sources DESC, n_articles DESC, last_seen DESC');
        $st->execute([$from, $to]);
        return $this->hydrate($st->fetchAll());
    }

    /**
     * Articles each enabled outlet published in [$from, $to), most first;
     * outlets with none are included with 0.
     * @return array<string,int>
     */
    public function articleCounts(int $from, int $to): array
    {
        $out = array_fill_keys(array_keys(App::sources()), 0);
        $st = $this->db->prepare('SELECT source_id, COUNT(*) n FROM articles WHERE published_at >= ? AND published_at < ? GROUP BY source_id');
        $st->execute([$from, $to]);
        foreach ($st->fetchAll() as $r) {
            if (isset($out[$r['source_id']])) {
                $out[$r['source_id']] = (int)$r['n'];
            }
        }
        arsort($out);
        return $out;
    }

    /** Ids of stories covered by 2+ outlets (the ones that get a page), optionally only those with an article since $since. */
    public function multiStoryIds(int $since = 0): array
    {
        $st = $this->db->prepare('SELECT id FROM stories WHERE n_sources >= 2 AND last_seen >= ? ORDER BY id');
        $st->execute([$since]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Number of articles from enabled outlets (optionally one side only), for paging Latest news. */
    public function countLatest(?string $side = null): int
    {
        $sourceIds = $this->sourceIds($side);
        if (!$sourceIds) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($sourceIds), '?'));
        $st = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE source_id IN ($in)");
        $st->execute($sourceIds);
        return (int)$st->fetchColumn();
    }

    /**
     * Every article from enabled outlets, newest first, with its story id
     * (0 unless 2+ outlets covered it), for the search index.
     * @return list<array<string,mixed>>
     */
    public function searchRows(): array
    {
        $sourceIds = $this->sourceIds(null);
        if (!$sourceIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($sourceIds), '?'));
        $st = $this->db->prepare(<<<SQL
            SELECT a.source_id, a.title, a.summary, a.url, a.published_at, COALESCE(s.id, 0) AS story_id
            FROM articles a
            LEFT JOIN story_articles sa ON sa.article_id = a.id
            LEFT JOIN stories s ON s.id = sa.story_id AND s.n_sources >= 2
            WHERE a.source_id IN ($in)
            ORDER BY a.published_at DESC, a.id DESC
        SQL);
        $st->execute($sourceIds);
        return $st->fetchAll();
    }

    /** Find the story that contains this article id (story ids are article ids). */
    public function story(int $articleId): ?array
    {
        $st = $this->db->prepare('SELECT s.* FROM stories s JOIN story_articles sa ON sa.story_id = s.id WHERE sa.article_id = ? LIMIT 1');
        $st->execute([$articleId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        return $this->hydrate([$row])[0];
    }

    public function sourceStats(): array
    {
        $since = time() - 86400;
        $st = $this->db->prepare('SELECT source_id, COUNT(*) n FROM articles WHERE published_at >= ? GROUP BY source_id');
        $st->execute([$since]);
        $counts = array_column($st->fetchAll(), 'n', 'source_id');
        $state = $this->feedState();
        $out = [];
        foreach (App::sources() as $id => $s) {
            $out[$id] = $s + ['id' => $id, 'last24h' => (int)($counts[$id] ?? 0), 'state' => $state[$id] ?? null];
        }
        return $out;
    }

    /** Attach articles, per-side counts and the representative article to story rows. */
    private function hydrate(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_map(fn ($r) => (int)$r['id'], $rows);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT sa.story_id, a.* FROM story_articles sa JOIN articles a ON a.id = sa.article_id WHERE sa.story_id IN ($in) ORDER BY a.published_at ASC");
        $st->execute($ids);
        $byStory = [];
        foreach ($st->fetchAll() as $a) {
            $byStory[(int)$a['story_id']][] = $a;
        }

        $sources = App::sources();
        $out = [];
        foreach ($rows as $r) {
            $articles = $byStory[(int)$r['id']] ?? [];
            $counts = ['gov' => 0, 'ind' => 0, 'opp' => 0];
            $seen = [];
            $rep = null;
            foreach ($articles as $a) {
                if ((int)$a['id'] === (int)$r['rep_article']) {
                    $rep = $a;
                }
                $sid = $a['source_id'];
                if (!isset($seen[$sid]) && isset($sources[$sid])) {
                    $seen[$sid] = true;
                    $counts[$sources[$sid]['label']]++;
                }
            }
            $rep ??= $articles[0] ?? null;
            if ($rep === null) {
                continue;
            }
            $r['articles'] = $articles;
            $r['rep'] = $rep;
            $r['counts'] = $counts;
            $r['image_article'] = $this->pickImageArticle($articles, $rep);
            $out[] = $r;
        }
        return $out;
    }

    /** Prefer the representative article's image; otherwise any member's. */
    private function pickImageArticle(array $articles, array $rep): ?int
    {
        if (!empty($rep['image'])) {
            return (int)$rep['id'];
        }
        foreach ($articles as $a) {
            if (!empty($a['image'])) {
                return (int)$a['id'];
            }
        }
        return null;
    }
}
