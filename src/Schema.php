<?php
declare(strict_types=1);

namespace Terazi;

final class Schema
{
    public static function migrate(\PDO $db): void
    {
        $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS feed_state (
            source_id     TEXT PRIMARY KEY,
            etag          TEXT,
            last_modified TEXT,
            last_fetch    INTEGER,
            last_status   INTEGER,
            last_error    TEXT,
            last_items    INTEGER
        );

        CREATE TABLE IF NOT EXISTS articles (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            source_id    TEXT NOT NULL,
            guid         TEXT NOT NULL,
            url          TEXT NOT NULL,
            title        TEXT NOT NULL,
            summary      TEXT NOT NULL DEFAULT '',
            image        TEXT,
            published_at INTEGER NOT NULL,
            fetched_at   INTEGER NOT NULL,
            UNIQUE (source_id, guid)
        );
        CREATE INDEX IF NOT EXISTS idx_articles_published ON articles (published_at);

        CREATE TABLE IF NOT EXISTS stories (
            id           INTEGER PRIMARY KEY,   -- = id of the story's earliest article, so links stay stable
            rep_article  INTEGER NOT NULL,      -- article whose headline represents the story
            n_sources    INTEGER NOT NULL,
            n_articles   INTEGER NOT NULL,
            first_seen   INTEGER NOT NULL,
            last_seen    INTEGER NOT NULL,
            score        REAL NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_stories_score ON stories (score DESC);

        CREATE TABLE IF NOT EXISTS story_articles (
            story_id   INTEGER NOT NULL,
            article_id INTEGER NOT NULL,
            PRIMARY KEY (story_id, article_id)
        );
        CREATE INDEX IF NOT EXISTS idx_story_articles_article ON story_articles (article_id);

        CREATE TABLE IF NOT EXISTS meta (
            key   TEXT PRIMARY KEY,
            value TEXT
        );
        SQL);
    }
}
