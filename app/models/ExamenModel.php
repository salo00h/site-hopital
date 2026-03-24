<?php
declare(strict_types=1);

/*
==================================================
  MODEL : ExamenModel
==================================================
  Rôle :
  - Accès aux données de la table examen.
  - Création, lecture et mise à jour des demandes d'examen.
  - Utilisation de PDO avec requêtes préparées.
  - Respect des noms réels des tables en lowercase.
==================================================
*/

require_once __DIR__ . '/../config/database.php';

/**
 * Crée une demande d'examen.
 */
function examen_create(int $idDossier, string $typeExamen, ?string $noteMedecin = null): bool
{
    $sql = "
        INSERT INTO examen (
            idDossier,
            typeExamen,
            noteMedecin,
            dateDemande,
            statut
        ) VALUES (
            :idDossier,
            :typeExamen,
            :noteMedecin,
            NOW(),
            :statut
        )
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':idDossier' => $idDossier,
        ':typeExamen' => $typeExamen,
        ':noteMedecin' => $noteMedecin,
        ':statut' => 'EN_ATTENTE',
    ]);
}

/**
 * Liste les examens d'un dossier.
 * Les plus récents apparaissent en premier.
 */
function examens_get_by_dossier(int $idDossier): array
{
    $sql = "
        SELECT
            idExamen,
            idDossier,
            typeExamen,
            noteMedecin,
            resultat,
            dateDemande,
            dateResultat,
            statut
        FROM examen
        WHERE idDossier = :idDossier
        ORDER BY dateDemande DESC, idExamen DESC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idDossier' => $idDossier,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Récupère une demande d'examen par son identifiant.
 */
function examen_get_by_id(int $idExamen): ?array
{
    $sql = "
        SELECT
            idExamen,
            idDossier,
            typeExamen,
            noteMedecin,
            resultat,
            dateDemande,
            dateResultat,
            statut
        FROM examen
        WHERE idExamen = :idExamen
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idExamen' => $idExamen,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Met à jour le statut d'un examen.
 */
function examen_update_statut(int $idExamen, string $statut): bool
{
    $allowed = ['EN_ATTENTE', 'EN_COURS', 'TERMINE', 'ANNULE'];

    if (!in_array($statut, $allowed, true)) {
        return false;
    }

    $sql = "
        UPDATE examen
        SET statut = :statut
        WHERE idExamen = :idExamen
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':statut' => $statut,
        ':idExamen' => $idExamen,
    ]);
}

/**
 * Retourne les demandes récentes.
 * Possibilité de filtrer par statut.
 */
function examens_get_recent(int $limit = 10, ?string $statut = null): array
{
    $limit = max(1, min($limit, 100));

    if ($statut !== null) {
        $sql = "
            SELECT
                idExamen,
                idDossier,
                typeExamen,
                noteMedecin,
                dateDemande,
                statut
            FROM examen
            WHERE statut = :statut
            ORDER BY dateDemande DESC, idExamen DESC
            LIMIT :lim
        ";

        $stmt = db()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $sql = "
        SELECT
            idExamen,
            idDossier,
            typeExamen,
            noteMedecin,
            dateDemande,
            statut
        FROM examen
        ORDER BY dateDemande DESC, idExamen DESC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne les examens récents avec les informations du patient.
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
        INNER JOIN dossier_patient d ON d.idDossier = e.idDossier
        INNER JOIN patient p ON p.idPatient = d.idPatient
        ORDER BY e.dateDemande DESC, e.idExamen DESC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne la liste des types d'examen.
 */
function examens_types_all(): array
{
    $sql = "
        SELECT *
        FROM type_examen
        ORDER BY idTypeExamen ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Enregistre le résultat d'un examen
 * et passe automatiquement son statut à TERMINE.
 */
function examen_save_resultat(int $idExamen, string $resultat): bool
{
    $sql = "
        UPDATE examen
        SET
            resultat = :resultat,
            dateResultat = NOW(),
            statut = :statut
        WHERE idExamen = :idExamen
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':resultat' => $resultat,
        ':statut' => 'TERMINE',
        ':idExamen' => $idExamen,
    ]);
}

/**
 * Compte le nombre d'examens en attente.
 */
function examens_count_en_attente(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM examen
        WHERE statut = :statut
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':statut' => 'EN_ATTENTE',
    ]);

    return (int) $stmt->fetchColumn();
}

/**
 * Retourne la liste des examens en attente
 * avec les informations du patient.
 */
function examens_get_en_attente_with_patient(int $limit = 10): array
{
    $limit = max(1, min($limit, 100));

    $sql = "
        SELECT
            e.idExamen,
            d.idDossier,
            p.nom,
            p.prenom,
            e.typeExamen,
            e.dateDemande
        FROM examen e
        INNER JOIN dossier_patient d ON d.idDossier = e.idDossier
        INNER JOIN patient p ON p.idPatient = d.idPatient
        WHERE e.statut = :statut
        ORDER BY e.dateDemande DESC, e.idExamen DESC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':statut', 'EN_ATTENTE', PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
