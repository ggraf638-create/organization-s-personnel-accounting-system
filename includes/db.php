<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $cfg = require __DIR__ . '/config.php';
    $c = $cfg['db'];
    $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
    $pdo = new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
