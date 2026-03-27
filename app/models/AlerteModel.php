<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MODEL : AlerteModel (PDO / MySQL)
|--------------------------------------------------------------------------
| Rôle :
| - Gérer uniquement les requêtes SQL liées aux alertes
| - Fournir les données nécessaires au dashboard
| - Ne contenir aucune logique métier ni affichage
| - Utiliser exclusivement les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/config/database.php';

/* =========================================================================
 * LECTURE DES ALERTES
 * ========================================================================= */

/**
 * Retourne les dernières alertes.
 *
 * Particularités :
 * - la colonne description est renommée en "message"
 *   pour rester compatible avec les vues
 * - la colonne action est également retournée
 * - la colonne statutAlerte est conservée si elle est utilisée ailleurs
 *
 * Tri :
 * - alertes les plus récentes en premier
 */
function alertes_get_last(int $limit = 5): array
{
    $pdo = db();

    $sql = "
        SELECT
            a.idAlerte,
            a.typeAlerte,
            a.description AS message,
            a.action,
            a.statutAlerte,
            a.dateCreation
        FROM alerte a
        ORDER BY a.dateCreation DESC, a.idAlerte DESC
        LIMIT :lim
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne le nombre total d'alertes.
 */
function alertes_count_all(): int
{
    $pdo = db();

    $sql = "
        SELECT COUNT(*) AS nb
        FROM alerte
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return (int) ($row['nb'] ?? 0);
}

/* =========================================================================
 * CRÉATION DES ALERTES
 * ========================================================================= */

/**
 * Crée une nouvelle alerte.
 *
 * Types possibles :
 * - saturation
 * - panne_Lit
 * - panne_Equipement
 * - Action
 * - demande_transfert
 *
 * Remarques :
 * - action peut être null si aucune action spécifique n'est requise
 * - statut initial = 'nonLu'
 */
function alerte_create(string $type, string $description, ?string $action = null): bool
{
    $sql = "
        INSERT INTO alerte (
            dateCreation,
            typeAlerte,
            action,
            statutAlerte,
            description
        ) VALUES (
            CURDATE(),
            :type,
            :action,
            :statutAlerte,
            :description
        )
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':type' => $type,
        ':action' => $action,
        ':statutAlerte' => 'nonLu',
        ':description' => $description,
    ]);
}
