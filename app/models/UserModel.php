<?php
declare(strict_types=1);

/*
==================================================
 MODEL : UserModel
==================================================
 Rôle :
 - Contenir uniquement les requêtes SQL liées aux utilisateurs.
 - Aucune logique métier ou affichage ici.
 - Utilisation de PDO avec requêtes préparées.
 - Respect des noms réels des tables en lowercase.
==================================================
*/

require_once __DIR__ . '/../config/database.php';

/**
 * Recherche un utilisateur actif par son nom d'utilisateur.
 * Retourne les informations de l'utilisateur si trouvé,
 * sinon retourne null.
 */
function findUserByUsername(string $username): ?array
{
    $sql = "
        SELECT *
        FROM users
        WHERE username = :username
          AND is_active = 1
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':username' => trim($username),
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}
