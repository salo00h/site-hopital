<?php
declare(strict_types=1);

/*
==================================================
 MODEL : AlerteModel (PDO / MySQL)
==================================================
 Rôle :
 - Contenir UNIQUEMENT du SQL lié aux alertes.
 - Fournir les dernières alertes pour le dashboard.
==================================================
*/

require_once APP_PATH . '/config/database.php';

/**
 * Renvoie les dernières alertes (ex: 5).
 *
 * IMPORTANT : dans la BD :
 * - table : ALERTE
 * - le texte est dans la colonne : description (pas "message")
 *
 * Pour ne pas casser la vue (qui affiche $a['message']),
 * on renvoie un alias "message" = description.
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
        FROM ALERTE a
        ORDER BY a.dateCreation DESC, a.idAlerte DESC
        LIMIT :lim
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}