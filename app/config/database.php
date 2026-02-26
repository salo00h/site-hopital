<?php
declare(strict_types=1);

// Fichier de connexion à la base de données.
// Compatible LOCAL + Render

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // إذا كنا على Render سيستعمل ENV
    // إذا كنا محلياً سيعود إلى config.php

    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');

    // إذا لم نجد ENV → نستعمل config.php (محلي)
    if (!$host) {
        $cfg = require __DIR__ . '/config.php';

        $host   = $cfg['host'];
        $port   = $cfg['port'];
        $dbname = $cfg['dbname'];
        $user   = $cfg['user'];
        $pass   = $cfg['pass'];
        $charset= $cfg['charset'];
    } else {
        $charset = 'utf8mb4';
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

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
