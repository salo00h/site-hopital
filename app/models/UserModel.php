<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MODEL : AuthModel
|--------------------------------------------------------------------------
| Rôle :
| - Gérer l'accès aux données liées à l'authentification
| - Rechercher les utilisateurs actifs
| - Ne contenir aucune logique métier (login, session, etc.)
| - Utiliser les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * RECHERCHE UTILISATEUR
 * ========================================================================= */

/**
 * Recherche un utilisateur actif par son username.
 *
 * Résultat :
 * - retourne les informations utilisateur si trouvé
 * - retourne null si aucun utilisateur correspondant
 *
 * Données récupérées :
 * - informations du compte (users)
 * - informations personnelles (personnel)
 */
function findUserByUsername(string $username): ?array
{
    $sql = "
        SELECT
            u.idUser,
            u.idPersonnel,
            u.username,
            u.password_hash,
            u.role,
            u.is_active,
            p.nom,
            p.prenom
        FROM users u
        INNER JOIN personnel p ON p.idPersonnel = u.idPersonnel
        WHERE u.username = :username
          AND u.is_active = 1
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':username' => $username,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}
