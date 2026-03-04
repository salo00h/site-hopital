<?php
declare(strict_types=1);

/*
==================================================
 MODEL : EquipementModel
==================================================
 Rôle :
 - Contenir uniquement les requêtes SQL liées aux équipements.
 - Aucune logique métier ou affichage ici.
 - Le contrôleur appelle ces fonctions.
 - Utilisation des constantes de tables (_tables.php)
   pour éviter les problèmes de majuscules/minuscules
   entre Windows et Linux (Render / Railway).
==================================================
*/

require_once APP_PATH . '/config/database.php';
require_once __DIR__ . '/_tables.php';


/**
 * Retourne les statistiques des équipements
 * (nombre disponibles / total par type).
 * Utilisé dans le dashboard médecin.
 */
function equipements_get_stats(): array
{
    $pdo = db();

    $sql = "
        SELECT
            typeEquipement AS type,
            SUM(CASE WHEN etatEquipement = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            COUNT(*) AS total
        FROM " . T_EQUIPEMENT . "
        GROUP BY typeEquipement
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retourne tous les équipements (tous les états).
 * Utilisé pour l'affichage dans la liste médecin.
 */
function equipements_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT
            idEquipement,
            typeEquipement,
            numeroEquipement,
            localisation,
            etatEquipement
        FROM " . T_EQUIPEMENT . "
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retourne uniquement les équipements disponibles.
 * Utilisé pour la réservation.
 */
function equipements_get_disponibles(): array
{
    $pdo = db();

    $sql = "
        SELECT
            idEquipement,
            typeEquipement,
            numeroEquipement,
            localisation,
            etatEquipement
        FROM " . T_EQUIPEMENT . "
        WHERE etatEquipement = 'disponible'
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retourne un équipement par son ID.
 * Permet de vérifier son état avant réservation.
 */
function equipement_get_by_id(int $idEquipement): ?array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . T_EQUIPEMENT . "
        WHERE idEquipement = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);

    $equipement = $stmt->fetch(PDO::FETCH_ASSOC);

    return $equipement ?: null;
}


/**
 * Change l’état d’un équipement en 'occupe'.
 */
function equipement_set_occupe(int $idEquipement): void
{
    $pdo = db();

    $sql = "
        UPDATE " . T_EQUIPEMENT . "
        SET etatEquipement = 'occupe'
        WHERE idEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);
}


/**
 * Ajoute un lien entre un dossier et un équipement (réservation).
 */
function gestion_equipement_add(int $idDossier, int $idEquipement): bool
{
    $pdo = db();

    if (!equipement_is_reservable($idEquipement)) {
        return false;
    }

    $sql = "INSERT INTO " . T_GESTION_EQUIPEMENT . " (idDossier, idEquipement) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);

    return (bool)$stmt->execute([$idDossier, $idEquipement]);
}


/**
 * Retourne les équipements liés à un dossier.
 */
function gestion_equipements_by_dossier(int $idDossier): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement
        FROM " . T_GESTION_EQUIPEMENT . " g
        JOIN " . T_EQUIPEMENT . " e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier = ?
        ORDER BY e.typeEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idDossier]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retourne un résumé des équipements par dossier.
 */
function equipements_resume_par_dossier(array $idsDossiers): array
{
    if (empty($idsDossiers)) {
        return [];
    }

    $pdo = db();

    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT
            g.idDossier,
            GROUP_CONCAT(CONCAT(e.typeEquipement, '#', e.numeroEquipement) ORDER BY e.typeEquipement SEPARATOR ', ') AS resume
        FROM " . T_GESTION_EQUIPEMENT . " g
        JOIN " . T_EQUIPEMENT . " e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier IN ($placeholders)
        GROUP BY g.idDossier
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsDossiers);

    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(int)$row['idDossier']] = (string)$row['resume'];
    }

    return $out;
}


/**
 * Mettre un équipement en panne.
 */
function equipement_set_panne(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        UPDATE " . T_EQUIPEMENT . "
        SET etatEquipement = 'en_panne'
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':id' => $idEquipement
    ]);
}


/**
 * Vérifie si l'équipement est réservable.
 */
function equipement_is_reservable(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        SELECT etatEquipement
        FROM " . T_EQUIPEMENT . "
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $idEquipement
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $etat = (string)($row['etatEquipement'] ?? '');

    return ($etat === 'disponible');
}


/**
 * Retourne l'état actuel de l'équipement.
 */
function equipement_get_etat(int $idEquipement): string
{
    $pdo = db();

    $sql = "
        SELECT etatEquipement
        FROM " . T_EQUIPEMENT . "
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $idEquipement
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (string)($row['etatEquipement'] ?? '');
}
