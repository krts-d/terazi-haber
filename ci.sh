#!/usr/bin/env bash
# One update of the site. GitHub runs this every 20 minutes
# (.github/workflows/publish.yml); you can run it locally too:
#
#   bash ci.sh [published site address]
#
# 1. Downloads the database the previous run published with the site, so the
#    last 7 days of headlines are kept between runs (skipped without an address).
# 2. Fetches the feeds and regroups the stories (fetch.php).
# 3. Builds the site into _site/ (build.php).
# 4. Puts the database into the site (_site/data/terazi.sqlite.gz) for the next run.
set -euo pipefail
cd "$(dirname "$0")"
site="${1:-}"
mkdir -p var

if [[ -n "$site" ]]; then
  if curl -fsSL --retry 2 --max-time 60 -o var/previous.sqlite.gz "$site/data/terazi.sqlite.gz?run=${GITHUB_RUN_ID:-local}" \
     && gunzip -t var/previous.sqlite.gz 2>/dev/null; then
    gunzip -c var/previous.sqlite.gz > var/terazi.sqlite
    echo "Continuing from the published database."
  else
    echo "No published database found; starting fresh."
  fi
  rm -f var/previous.sqlite.gz
fi

# fetch.php exits with 2 when every feed failed: still build, with the old headlines.
status=0
php fetch.php || status=$?
if [[ $status -eq 2 ]]; then
  echo "::warning::Every feed failed this time; the site keeps the previous headlines."
elif [[ $status -ne 0 ]]; then
  exit "$status"
fi

php build.php _site

# One self-contained file (no write-ahead log next to it), compressed.
php -r '(new PDO("sqlite:" . $argv[1]))->exec("PRAGMA journal_mode = DELETE");' var/terazi.sqlite
mkdir -p _site/data
gzip -9 -c var/terazi.sqlite > _site/data/terazi.sqlite.gz
echo "Database: $(du -h _site/data/terazi.sqlite.gz | cut -f1) compressed."
