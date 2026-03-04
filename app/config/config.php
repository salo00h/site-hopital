<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    
    if (getenv('MYSQLHOST')) {

        $host = getenv('MYSQLHOST');
        $port = getenv('MYSQLPORT');
        $db   = getenv('MYSQLDATABASE');
        $user = getenv('MYSQLUSER');
        $pass = getenv('MYSQLPASSWORD');

    } else {
        $cfg = require __DIR__ . '/config.php';

        $host = $cfg['host'];
        $port = $cfg['port'];
        $db   = $cfg['dbname'];
        $user = $cfg['user'];
        $pass = $cfg['pass'];
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function connectBD(): PDO
{
    return db();
}
