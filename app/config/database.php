<?php
declare(strict_types=1);

// Fichier de connexion à la base de données.
// Ce fichier crée une seule connexion PDO (singleton)
// pour éviter d’ouvrir plusieurs connexions inutiles.

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null; // garde la connexion en mémoire

    // Si la connexion existe déjà, on la retourne
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // On récupère les paramètres depuis config.php
    $cfg = require __DIR__ . '/config.php';

    // Construction du DSN pour MySQL
    $dsn = 'mysql:host=' . $cfg['host']
         . ';port=' . $cfg['port']
         . ';dbname=' . $cfg['dbname']
         . ';charset=' . $cfg['charset'];

    // Création de la connexion PDO avec options de sécurité
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // affiche les erreurs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // tableau associatif
    ]);

    return $pdo;
}

// Fonction alternative pour garder le nom utilisé dans le projet
function connectBD(): PDO
{
    return db();
}