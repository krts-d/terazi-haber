<?php
declare(strict_types=1);

namespace Terazi;

/**
 * Checks that PHP has the modules Terazi needs and that var/ is writable.
 * Without this, a missing module shows up as a blank "500 Internal Server
 * Error" page. With it, the page and the terminal name the missing package.
 */
final class Requirements
{
    /** PHP extension => [Fedora package, Debian/Ubuntu package] */
    private const EXTENSIONS = [
        'pdo_sqlite' => ['php-pdo', 'php-sqlite3'],
        'mbstring'   => ['php-mbstring', 'php-mbstring'],
        'simplexml'  => ['php-xml', 'php-xml'],
        'curl'       => ['php-common', 'php-curl'],
        'fileinfo'   => ['php-common', 'php-common'],
    ];

    public static function check(): void
    {
        $problems = [];

        $missing = array_values(array_filter(array_keys(self::EXTENSIONS), fn ($e) => !extension_loaded($e)));
        if ($missing) {
            $fedora = array_unique(array_map(fn ($e) => self::EXTENSIONS[$e][0], $missing));
            $debian = array_unique(array_map(fn ($e) => self::EXTENSIONS[$e][1], $missing));
            $problems[] = [
                'PHP is missing ' . (count($missing) === 1 ? 'a module' : 'modules') . ': ' . implode(', ', $missing) . '.',
                "Fedora:          sudo dnf install " . implode(' ', $fedora) . "\n"
                . "Debian/Ubuntu:   sudo apt install " . implode(' ', $debian) . "\n"
                . 'Then restart the web server (stop php -S with Ctrl+C and start it again).',
            ];
        }

        $dir = dirname((string)App::config('db_path'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            $user = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
                ? (posix_getpwuid(posix_geteuid())['name'] ?? 'the web server user')
                : 'the web server user';
            $problems[] = [
                "PHP (running as $user) can't write to $dir",
                "That folder holds the database and image cache. Give $user write access to it.\n"
                . "On Fedora with Apache or nginx, SELinux also has to allow it:\n"
                . "  sudo chcon -R -t httpd_sys_rw_content_t $dir",
            ];
        }

        if ($problems) {
            self::fail('Terazi can’t start yet', $problems);
        }
    }

    /**
     * Turn uncaught errors on web pages into a readable page instead of a
     * blank 500. Visitors on a public server only see a short apology;
     * the details go to the web server's error log.
     */
    public static function handleWebErrors(): void
    {
        // Error logs must not pick up what visitors typed (search words are
        // function arguments), so leave arguments out of stack traces.
        ini_set('zend.exception_ignore_args', '1');
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (!self::isLocal()) {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }
        set_exception_handler(function (\Throwable $e): void {
            error_log('Terazi: ' . $e);
            $detail = get_class($e) . ': ' . $e->getMessage() . "\nin " . $e->getFile() . ':' . $e->getLine();
            self::fail('Something went wrong', [['This page hit an error.', $detail]], logged: true);
        });
    }

    /** php -S on your own computer: safe to show error details. */
    private static function isLocal(): bool
    {
        return PHP_SAPI === 'cli-server';
    }

    /**
     * @param list<array{0:string,1:string}> $problems [headline, how to fix]
     * @param bool $logged the details are in the error log already
     */
    private static function fail(string $title, array $problems, bool $logged = false): never
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "\n$title\n\n");
            foreach ($problems as [$what, $fix]) {
                fwrite(STDERR, "  $what\n" . preg_replace('/^/m', '    ', $fix) . "\n\n");
            }
            exit(1);
        }

        // Throw away the half-built page, including its compression.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header_remove();
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
        }
        $lang = 'en';
        if (!self::isLocal()) {
            // Public server: details to the error log, a plain apology to the visitor.
            foreach ($logged ? [] : $problems as [$what, $fix]) {
                error_log("Terazi: $title: $what\n$fix");
            }
            try {
                $lang = Lang::lang();
                $problems = [[Lang::t('error.body'), '']];
                $title = Lang::t('error.title');
            } catch (\Throwable) {
                $problems = [['This page can’t be shown right now. Please try again in a moment.', '']];
            }
        }
        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<!doctype html><html lang="' . $e($lang) . '"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $e($title) . '</title><style>'
            . ':root{color-scheme:light dark;--bg:#fcfbf8;--fg:#1d1c1a;--mut:#5a564f;--box:#f1eee7}'
            . '@media (prefers-color-scheme:dark){:root{--bg:#171614;--fg:#ece8df;--mut:#b5b0a5;--box:#24221f}}'
            . 'body{margin:0;background:var(--bg);color:var(--fg);font:17px/1.5 Georgia,serif}'
            . 'main{max-width:680px;margin:0 auto;padding:48px 16px}'
            . 'h1{font-size:2rem;line-height:1.1;margin:0 0 24px;border-bottom:3px double currentColor;padding-bottom:12px}'
            . 'p{margin:0 0 8px;font-weight:600}pre{background:var(--box);color:var(--fg);padding:12px 14px;overflow-x:auto;'
            . 'font:14px/1.5 ui-monospace,monospace;margin:0 0 28px;white-space:pre-wrap;word-break:break-word}'
            . '</style></head><body><main><h1>' . $e($title) . '</h1>';
        foreach ($problems as [$what, $fix]) {
            echo '<p>' . $e($what) . '</p>' . ($fix !== '' ? '<pre>' . $e($fix) . '</pre>' : '');
        }
        echo '</main></body></html>';
        exit;
    }
}
