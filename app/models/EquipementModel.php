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
==================================================
*/

require_once APP_PATH . '/config/database.php';


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
        FROM EQUIPEMENT
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
        FROM EQUIPEMENT
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
        FROM EQUIPEMENT
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
        FROM EQUIPEMENT
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
 * Appelé après vérification de disponibilité.
 */
function equipement_set_occupe(int $idEquipement): void
{
    $pdo = db();

    $sql = "
        UPDATE EQUIPEMENT
        SET etatEquipement = 'occupe'
        WHERE idEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);
}

/**
 * Ajoute un lien entre un dossier et un équipement (réservation).
 * On stocke la relation dans GESTION_EQUIPEMENT.
 */
function gestion_equipement_add(int $idDossier, int $idEquipement): void
{
    $pdo = db();

    $sql = "INSERT INTO GESTION_EQUIPEMENT (idDossier, idEquipement) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);

    $ok = $stmt->execute([$idDossier, $idEquipement]);


    if (!$ok) {
        $err = $stmt->errorInfo();
        exit('ERREUR INSERT GESTION_EQUIPEMENT: ' . ($err[2] ?? 'unknown'));
    }
}

/**
 * Retourne les équipements liés à un dossier.
 * Utilisé uniquement dans la vue médecin.
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
        FROM GESTION_EQUIPEMENT g
        JOIN EQUIPEMENT e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier = ?
        ORDER BY e.typeEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idDossier]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Retourne un résumé des équipements par dossier.
 * Exemple: [ 10 => "Cardiologie#2, Radiologie#1", ... ]
 */
function equipements_resume_par_dossier(array $idsDossiers): array
{
    if (empty($idsDossiers)) {
        return [];
    }

    $pdo = db();

    // placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT
            g.idDossier,
            GROUP_CONCAT(CONCAT(e.typeEquipement, '#', e.numeroEquipement) ORDER BY e.typeEquipement SEPARATOR ', ') AS resume
        FROM GESTION_EQUIPEMENT g
        JOIN EQUIPEMENT e ON e.idEquipement = g.idEquipement
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