<?php

declare(strict_types=1);

// Modèle User (partie M du MVC).
// Ce fichier contient la logique d’accès aux données
// concernant les utilisateurs.
// Il communique avec la base de données,
// mais ne contient aucune logique d’affichage.

require_once __DIR__ . '/../config/database.php';

// Recherche un utilisateur actif par son username.
// Retourne les informations de l’utilisateur si trouvé,
// sinon retourne null.
function findUserByUsername(string $username): ?array
{
    $sql = 'SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1';
    
    // Préparation sécurisée de la requête (évite les injections SQL)
    $stmt = db()->prepare($sql);
    $stmt->execute([$username]);

    $row = $stmt->fetch();

    // Si aucun résultat → retourne null
    return $row ?: null;
}