<?php
declare(strict_types=1);

/*
==================================================
 MODEL : AlerteModel (PDO / MySQL)
==================================================
 Rôle :
 - Contenir uniquement les requêtes SQL liées aux alertes.
 - Fournir les alertes pour le dashboard.
 - Utiliser PDO avec requêtes préparées.
 - Respecter les noms réels des tables en lowercase.
==================================================
*/

require_once APP_PATH . '/config/database.php';

/**
 * Retourne les dernières alertes.
 *
 * Remarque :
 * - Le texte est stocké dans la colonne description.
 * - On renvoie un alias "message" pour rester compatible avec la vue.
 */
function alertes_get_last(int $limit = 5): array
{
    $pdo = db();

    $sql = "
        SELECT
            a.idAlerte,
            a.dateCreation,
            a.typeAlerte,
            a.statutAlerte,
            a.description AS message
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

/**
 * Crée une nouvelle alerte.
 *
 * Types possibles selon le projet :
 * - saturation
 * - panne_Lit
 * - panne_Equipement
 * - Action
 * - demande_transfert
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
