<?php
declare(strict_types=1);

namespace Terazi;

const LABELS = ['gov', 'ind', 'opp'];

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Terazi\\')) {
        $file = __DIR__ . '/' . substr($class, 7) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

final class App
{
    private static ?array $config = null;
    private static ?array $sources = null;
    private static ?\PDO $db = null;

    public static function config(?string $key = null): mixed
    {
        if (self::$config === null) {
            $root = dirname(__DIR__);
            $cfg = require $root . '/config.php';
            date_default_timezone_set($cfg['timezone']);
            self::$config = $cfg;
        }
        return $key === null ? self::$config : (self::$config[$key] ?? null);
    }

    /** @return array<string, array{name:string,label:string,site:string,feed:string}> enabled outlets only */
    public static function sources(): array
    {
        if (self::$sources === null) {
            $root = dirname(__DIR__);
            $all = require $root . '/sources.php';
            $out = [];
            foreach ($all as $id => $s) {
                if (($s['enabled'] ?? true) === false) {
                    continue;
                }
                if (!in_array($s['label'] ?? '', LABELS, true)) {
                    throw new \RuntimeException("Source '$id' has an unknown label '" . ($s['label'] ?? '') . "'. Use gov, ind or opp.");
                }
                $out[(string)$id] = $s;
            }
            self::$sources = $out;
        }
        return self::$sources;
    }

    public static function db(): \PDO
    {
        if (self::$db === null) {
            $path = self::config('db_path');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new \PDO('sqlite:' . $path, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA foreign_keys = ON');
            Schema::migrate($pdo);
            self::$db = $pdo;
        }
        return self::$db;
    }
}

// Fail with a clear message (not a blank 500) when PHP modules are missing
// or var/ isn't writable.
Requirements::handleWebErrors();
Requirements::check();
