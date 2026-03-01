<?php
declare(strict_types=1);

/*
==================================================
 MODEL : EquipementModel (PDO / MySQL)
==================================================
 Rôle :
 - Contenir UNIQUEMENT du SQL lié aux équipements.
 - Fournir des statistiques simples pour le dashboard.
==================================================
*/

require_once APP_PATH . '/config/database.php';

/**
 * Statistiques équipements : disponibles / total par type
 * IMPORTANT : dans la BD :
 * - table : EQUIPEMENT
 * - colonne type : typeEquipement
 * - colonne état : etatEquipement (disponible, occupe, en_panne, maintenance, HS)
 *
 * Format retourné (pour la vue accueil_medecin.php) :
 * [
 *   ['type' => 'respirateur', 'disponibles' => 7, 'total' => 20],
 *   ...
 * ]
 */
function equipements_get_stats(): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.typeEquipement AS type,
            SUM(CASE WHEN e.etatEquipement = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            COUNT(*) AS total
        FROM EQUIPEMENT e
        GROUP BY e.typeEquipement
        ORDER BY e.typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}