<?php

/*
|--------------------------------------------------------------------------
| Configuration de la base de données
|--------------------------------------------------------------------------
| Ce fichier lit d'abord les variables d'environnement du serveur
| (Railway / Render). Si elles n'existent pas, il utilise les valeurs
| locales pour travailler sur Windows en développement.
*/

return [
    'host' => getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost',
    'port' => (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3307),
    'dbname' => getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'filrouge',
    'user' => getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: 'S@leh',
    'charset' => 'utf8mb4',
];
