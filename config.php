<?php
/**
 * Terazi (GitHub Pages version) — settings.
 * Outlets and their labels live in sources.php.
 */
return [
    // Interface language: 'en' or 'tr'
    'lang' => 'tr',

    'site_name' => 'Terazi',
    'timezone'  => 'Europe/Istanbul',

    // Who runs the site. Required before publishing: KVKK (Law 6698) says the
    // privacy notice must name the data controller, and Law 5651 says a site
    // must show its owner's contact details. Shown on the Privacy page.
    // This file is public on GitHub, like the Privacy page itself.
    'operator' => [
        'name'    => 'john doe',   // your full name, or your company's registered name
        'email'   => 'johndoe@johndoe.com',   // where KVKK requests and questions can be sent
        'address' => 'N/A',   // postal address for written KVKK requests (a business address or KEP address works)
    ],

    // Where the site is hosted. Shown on the Privacy page.
    'hosting' => [
        'provider' => 'GitHub, Inc. (GitHub Pages)',
        'country'  => 'ABD',   // in the site's language: 'ABD' (tr) or 'USA' (en)
    ],

    // SQLite database. On GitHub it is carried from one run to the next
    // inside the published site (data/terazi.sqlite.gz); see ci.sh.
    'db_path' => __DIR__ . '/var/terazi.sqlite',

    // Feed fetching
    'fetch_timeout'   => 20,       // seconds per feed
    'user_agent'      => 'Terazi/1.0 (news comparison; RSS reader)',
    'keep_days'       => 7,        // articles older than this are deleted

    // Story grouping
    'cluster_window_hours' => 48,   // only articles this recent are grouped
    'cluster_max_gap_hours' => 30,  // an article can join a story at most this long after its newest item
    'cluster_threshold'    => 0.20, // similarity needed to join a story (raise if unrelated items merge, lower if one event splits)
    'cluster_min_shared'   => 2,    // distinct words (headline + summary) an article must share with a story

    // News photos are not shown on the GitHub version: copying the outlets'
    // photos onto GitHub invites copyright takedown notices.
    'show_images' => false,

    // Front page
    'front_stories'     => 13,  // lead + grid
    'blindspot_min_sources' => 3,

    // Latest news: how many pages (40 headlines each) are built for each side.
    // Older headlines can still be found with search.
    'news_pages' => 10,
];
