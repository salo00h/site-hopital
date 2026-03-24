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
 - Les noms des tables respectent la base réelle en lowercase.
==================================================
*/

require_once APP_PATH . '/config/database.php';

/**
 * Retourne les statistiques des équipements
 * par type : nombre disponibles et total.
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
        FROM equipement
        GROUP BY typeEquipement
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Compte le nombre total d'équipements disponibles.
 */
function equipements_count_disponibles(): int
{
    $pdo = db();

    $sql = "
        SELECT COUNT(*) AS nb
        FROM equipement
        WHERE etatEquipement = :etat
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':etat' => 'disponible',
    ]);

    return (int) $stmt->fetchColumn();
}

/**
 * Retourne tous les équipements.
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
        FROM equipement
        ORDER BY typeEquipement ASC, numeroEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un équipement par son ID.
 * Permet de vérifier son état avant réservation.
 */
function equipement_get_by_id(int $idEquipement): ?array
{
    $pdo = db();

    $sql = "
        SELECT
            idEquipement,
            typeEquipement,
            numeroEquipement,
            etatEquipement,
            localisation,
            idService
        FROM equipement
        WHERE idEquipement = :idEquipement
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idEquipement' => $idEquipement,
    ]);

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
        UPDATE equipement
        SET etatEquipement = :etat
        WHERE idEquipement = :idEquipement
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':etat' => 'occupe',
        ':idEquipement' => $idEquipement,
    ]);
}

/**
 * Ajoute un lien entre un dossier et un équipement.
 *
 * Important :
 * - Retourne false si l'équipement n'est pas réservable.
 * - Retourne true si le lien existe déjà.
 * - Ne fait pas de sortie directe, le contrôleur gère la suite.
 */
function gestion_equipement_add(int $idDossier, int $idEquipement): bool
{
    $pdo = db();

    if (!equipement_is_reservable($idEquipement)) {
        return false;
    }

    $checkSql = "
        SELECT COUNT(*) AS nb
        FROM gestion_equipement
        WHERE idDossier = :idDossier
          AND idEquipement = :idEquipement
    ";

    $check = $pdo->prepare($checkSql);
    $check->execute([
        ':idDossier' => $idDossier,
        ':idEquipement' => $idEquipement,
    ]);

    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ((int)($row['nb'] ?? 0) > 0) {
        return true;
    }

    $sql = "
        INSERT INTO gestion_equipement (idDossier, idEquipement)
        VALUES (:idDossier, :idEquipement)
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idDossier' => $idDossier,
        ':idEquipement' => $idEquipement,
    ]);
}

/**
 * Retourne les équipements liés à un dossier.
 * Utilisé dans la vue médecin.
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
        FROM gestion_equipement g
        INNER JOIN equipement e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier = :idDossier
        ORDER BY e.typeEquipement ASC, e.numeroEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idDossier' => $idDossier,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un résumé des équipements par dossier.
 * Exemple :
 * [10 => "Scanner#2, Moniteur#1"]
 */
function equipements_resume_par_dossier(array $idsDossiers): array
{
    $idsDossiers = array_values(array_unique(array_map('intval', $idsDossiers)));
    $idsDossiers = array_values(array_filter($idsDossiers, static fn(int $id): bool => $id > 0));

    if (empty($idsDossiers)) {
        return [];
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT
            g.idDossier,
            GROUP_CONCAT(
                CONCAT(e.typeEquipement, '#', e.numeroEquipement)
                ORDER BY e.typeEquipement, e.numeroEquipement
                SEPARATOR ', '
            ) AS resume
        FROM gestion_equipement g
        INNER JOIN equipement e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier IN ($placeholders)
        GROUP BY g.idDossier
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsDossiers);

    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int)$row['idDossier']] = (string)$row['resume'];
    }

    return $result;
}

/**
 * Met un équipement en panne.
 */
function equipement_set_panne(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = :etat
        WHERE idEquipement = :id
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':etat' => 'en_panne',
        ':id' => $idEquipement,
    ]);
}

/**
 * Vérifie si un équipement est réservable.
 * Un équipement est réservable seulement si son état est 'disponible'.
 */
function equipement_is_reservable(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        SELECT etatEquipement
        FROM equipement
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $idEquipement,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $etat = (string)($row['etatEquipement'] ?? '');

    return $etat === 'disponible';
}

/**
 * Met à jour l’état d’un équipement.
 */
function equipement_update_etat(int $idEquipement, string $etat): bool
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = :etat
        WHERE idEquipement = :id
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':etat' => $etat,
        ':id' => $idEquipement,
    ]);
}

/**
 * Change l’état d’un équipement en 'reserve'.
 */
function equipement_set_reserve(int $idEquipement): void
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = :etat
        WHERE idEquipement = :idEquipement
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':etat' => 'reserve',
        ':idEquipement' => $idEquipement,
    ]);
}

/**
 * Retourne tous les équipements avec le patient lié si présent.
 */
function equipements_get_all_with_patient(): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement,
            d.idDossier,
            p.nom,
            p.prenom
        FROM equipement e
        LEFT JOIN gestion_equipement g
            ON g.idEquipement = e.idEquipement
        LEFT JOIN dossier_patient d
            ON d.idDossier = g.idDossier
        LEFT JOIN patient p
            ON p.idPatient = d.idPatient
        ORDER BY
            CASE e.etatEquipement
                WHEN 'reserve' THEN 1
                WHEN 'occupe' THEN 2
                WHEN 'disponible' THEN 3
                WHEN 'en_panne' THEN 4
                WHEN 'maintenance' THEN 5
                WHEN 'HS' THEN 6
                ELSE 99
            END,
            e.typeEquipement ASC,
            e.numeroEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Supprime les liens de gestion pour un équipement donné.
 */
function gestion_equipement_delete_by_equipement(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        DELETE FROM gestion_equipement
        WHERE idEquipement = :idEquipement
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idEquipement' => $idEquipement,
    ]);
}
