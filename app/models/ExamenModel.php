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
  - Utilisation des constantes de tables (_tables.php)
    pour éviter les problèmes de majuscules/minuscules
    entre Windows et Linux (Render / Railway).
==================================================
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_tables.php';

/**
 * Crée une demande d'examen.
 */
function examen_create(int $idDossier, string $typeExamen, ?string $noteMedecin = null): bool
{
    $sql = "
        INSERT INTO " . T_EXAMEN . " (idDossier, typeExamen, note_medecin, dateDemande, statut)
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
        SELECT idExamen, idDossier, typeExamen, note_medecin, dateDemande, statut
        FROM " . T_EXAMEN . "
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
        SELECT idExamen, idDossier, typeExamen, note_medecin, dateDemande, statut
        FROM " . T_EXAMEN . "
        WHERE idExamen = :idExamen
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':idExamen' => $idExamen]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Met à jour le statut.
 */
function examen_update_statut(int $idExamen, string $statut): bool
{
    $allowed = ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'ANNULE'];
    if (!in_array($statut, $allowed, true)) {
        return false;
    }

    $sql = "UPDATE " . T_EXAMEN . " SET statut = :statut WHERE idExamen = :idExamen LIMIT 1";
    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':statut'   => $statut,
        ':idExamen' => $idExamen,
    ]);
}

/**
 * Liste des examens récents.
 */
function examens_get_recent(int $limit = 10, ?string $statut = null): array
{
    $limit = max(1, min($limit, 100));

    if ($statut !== null) {
        $sql = "
            SELECT idExamen, idDossier, typeExamen, note_medecin, dateDemande, statut
            FROM " . T_EXAMEN . "
            WHERE statut = :statut
            ORDER BY dateDemande DESC
            LIMIT $limit
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([':statut' => $statut]);
        return $stmt->fetchAll();
    }

    $sql = "
        SELECT idExamen, idDossier, typeExamen, note_medecin, dateDemande, statut
        FROM " . T_EXAMEN . "
        ORDER BY dateDemande DESC
        LIMIT $limit
    ";

    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

/**
 * Liste avec infos patient.
 */
function examens_get_recent_with_patient(int $limit = 10): array
{
    $limit = max(1, min($limit, 100));

    $sql = "
        SELECT
            e.idExamen,
            e.typeExamen,
            e.note_medecin,
            e.dateDemande,
            e.statut,
            d.idDossier,
            p.idPatient,
            p.nom,
            p.prenom
        FROM " . T_EXAMEN . " e
        JOIN " . T_DOSSIER . " d ON d.idDossier = e.idDossier
        JOIN " . T_PATIENT . " p ON p.idPatient = d.idPatient
        ORDER BY e.dateDemande DESC
        LIMIT $limit
    ";

    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

/**
 * Liste des types d'examens.
 */
function examens_types_all(): array
{
    $sql = "SELECT libelle FROM type_examen ORDER BY libelle";
    return db()->query($sql)->fetchAll();
}
