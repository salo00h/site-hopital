<?php
declare(strict_types=1);

/*
==================================================
  MODEL : ExamenModel
==================================================
  Rôle :
  - Accès aux données (table EXAMEN).
  - Création + lecture des demandes d'examen.
  - PDO + requêtes préparées.
==================================================
*/

require_once __DIR__ . '/../config/database.php';

/**
 * Crée une demande d'examen.
 */
function examen_create(int $idDossier, string $typeExamen, ?string $noteMedecin = null): bool
{
    $sql = "
        INSERT INTO examen (idDossier, typeExamen, noteMedecin, dateDemande, statut)
        VALUES (:idDossier, :typeExamen, :noteMedecin, NOW(), 'EN_ATTENTE')
    ";

    $stmt = db()->prepare($sql);
    return $stmt->execute([
        ':idDossier'   => $idDossier,
        ':typeExamen'  => $typeExamen,
        ':noteMedecin' => $noteMedecin,
    ]);
}

/**
 * Liste des examens d'un dossier (plus récent d'abord).
 */
function examens_get_by_dossier(int $idDossier): array
{
    $sql = "
        SELECT idExamen, idDossier, typeExamen, noteMedecin, dateDemande, statut
        FROM examen
        WHERE idDossier = :idDossier
        ORDER BY dateDemande DESC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':idDossier' => $idDossier]);
    return $stmt->fetchAll();
}

/**
 * Récupère une demande par son id.
 */
function examen_get_by_id(int $idExamen): ?array
{
    $sql = "
        SELECT idExamen, idDossier, typeExamen, noteMedecin, dateDemande, statut
        FROM examen
        WHERE idExamen = :idExamen
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':idExamen' => $idExamen]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Met à jour le statut (EN_ATTENTE | EN_COURS | TERMINE | ANNULE).
 */
function examen_update_statut(int $idExamen, string $statut): bool
{
    $allowed = ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'ANNULE'];
    if (!in_array($statut, $allowed, true)) {
        return false;
    }

    $sql = "UPDATE examen SET statut = :statut WHERE idExamen = :idExamen LIMIT 1";
    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':statut'   => $statut,
        ':idExamen' => $idExamen,
    ]);
}

/**
 * Liste des demandes récentes (pour dashboard).
 * Option: filtrer par statut.
 */
function examens_get_recent(int $limit = 10, ?string $statut = null): array
{
    $limit = max(1, min($limit, 100));

    if ($statut !== null) {
        $sql = "
            SELECT idExamen, idDossier, typeExamen, noteMedecin, dateDemande, statut
            FROM examen
            WHERE statut = :statut
            ORDER BY dateDemande DESC
            LIMIT $limit
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':statut' => $statut]);
        return $stmt->fetchAll();
    }

    $sql = "
        SELECT idExamen, idDossier, typeExamen, noteMedecin, dateDemande, statut
        FROM examen
        ORDER BY dateDemande DESC
        LIMIT $limit
    ";

    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

/**
 * (Optionnel) Liste avec infos patient via dossier.
 * Pour afficher sur dashboard: nom/prénom + type + statut...
 */
function examens_get_recent_with_patient(int $limit = 10): array
{
    $limit = max(1, min($limit, 100));

    $sql = "
        SELECT
            e.idExamen,
            e.typeExamen,
            e.noteMedecin,
            e.dateDemande,
            e.statut,
            d.idDossier,
            p.idPatient,
            p.nom,
            p.prenom
        FROM examen e
        JOIN dossier_patient d ON d.idDossier = e.idDossier
        JOIN patient p ON p.idPatient = d.idPatient
        ORDER BY e.dateDemande DESC
        LIMIT $limit
    ";

    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}


/**
 * Retourne la liste des types d'examens (pour le formulaire).
 */
function examens_types_all(): array
{
    $sql = "SELECT libelle FROM type_examen ORDER BY libelle";
    return db()->query($sql)->fetchAll();
}


