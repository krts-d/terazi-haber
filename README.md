# Terazi (GitHub Pages version)

A news site in a classic newspaper layout that groups Turkish headlines by
event and shows how **pro-government**, **independent** and **pro-opposition**
outlets each covered it. This version runs entirely on GitHub, for free: no
server of your own.

GitHub Pages can only host ready-made files, so a GitHub Actions job does the
work every 20 minutes: it fetches the outlets' RSS feeds, groups the stories,
turns every page into plain HTML and publishes the result.

## Publishing it

1. **Fill in your details** in `config.php` under `operator`: your name (or
   company), email and postal address. KVKK and Law 5651 require them on the
   Privacy page. A business or KEP address works if you don't want your home
   address public. This file is public on GitHub too.
2. **Create a public repository** on github.com (e.g. `terazi`). On a free
   account, GitHub Pages only works with public repositories.
3. **Upload this folder** to it. From this folder:
   ```sh
   git init -b main
   git add .
   git commit -m "Terazi"
   git remote add origin https://github.com/YOUR-NAME/terazi.git
   git push -u origin main
   ```
4. **Turn on Pages:** in the repository, *Settings → Pages → Build and
   deployment → Source*: choose **GitHub Actions**.
5. **Run it once:** *Actions → Update and publish → Run workflow*. After a
   minute or two the site is at `https://YOUR-NAME.github.io/terazi/`.

From then on it updates itself every 20 minutes, and whenever you push a change.

## Good to know

- **Timing.** GitHub sometimes starts scheduled runs late or skips one when it
  is busy, so updates can be 20–40 minutes apart.
- **Pausing.** GitHub pauses scheduled runs in repositories with no commits for
  60 days. The workflow makes an empty commit every 50 quiet days to prevent that.
  If it ever stops, *Actions → Update and publish → Enable workflow*.
- **Failing feeds.** The feeds are fetched from GitHub's servers in the USA. If
  an outlet blocks them, the Outlets page shows its feed as not working; the run
  log (*Actions*) shows the reason.
- **No news photos.** Copying the outlets' photos onto GitHub invites copyright
  takedown notices, which can get the whole repository disabled.
- **The database is public.** Each run continues from the previous one's
  database, which is published with the site (`data/terazi.sqlite.gz`). It holds
  only the outlets' public headlines, never anything about visitors.
- **Limits.** GitHub Pages allows 1 GB per site and about 100 GB of traffic a
  month. A full build is about 10 MB; a page view is about 10 KB once the
  fonts (186 KB, loaded on the first visit) are cached.

## Privacy

The Privacy page (`public/privacy.php`, text in `src/Lang.php`) is the KVKK
information notice, written for GitHub hosting. The site sets no cookies, uses
no analytics or ads, and loads nothing from anywhere but GitHub. Search runs
inside the reader's browser: the words are never sent to any server.

What the site can't control: **GitHub logs every visitor's IP address** for
security, and its servers are in the USA. The Privacy page says so, as KVKK
requires. Whether that is enough under KVKK Article 9 (transfers abroad) is a
question for a lawyer; hosting on a server in Türkiye (see the PHP version)
avoids it.

## Trying it on your own computer

```sh
php fetch.php                       # fetch the feeds into var/
php build.php                       # build the site into _site/
php -S localhost:8080 -t _site      # open http://localhost:8080
```

`bash ci.sh` does what the GitHub job does (fetch, build, pack the database).

## Changing things

- **Outlets and labels:** `sources.php`. `php fetch.php --check` tests every feed.
- **Settings** (language, how many news pages, grouping): `config.php`.
- **Pages:** the templates in `public/` are ordinary PHP, rendered once per
  build by `build.php`. Every link goes through `View::link()`.

## Files

```
config.php          settings (your details, language, grouping)
sources.php         outlets, feed URLs and labels
fetch.php           fetch + store + group
build.php           render every page into _site/ (uses render.php)
ci.sh               one full update: what the GitHub job runs
.github/workflows/  the GitHub job (every 20 minutes)
src/                parser, fetcher, grouping, storage, page helpers, TR/EN text
public/             page templates and assets (fonts, CSS, scripts)
```

Fonts: Newsreader and Archivo Narrow, SIL Open Font License (licenses in
`public/assets/fonts`).
