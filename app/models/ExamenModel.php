<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MODEL : ExamenModel
|--------------------------------------------------------------------------
| Rôle :
| - Gérer l'accès aux données de la table examen
| - Créer et lire les demandes d'examen
| - Mettre à jour le statut et les résultats
| - Utiliser PDO et les requêtes préparées
| - Respecter les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * CRÉATION DES DEMANDES D’EXAMEN
 * ========================================================================= */

/**
 * Crée une demande d'examen.
 *
 * Remarques :
 * - idMedecin peut être null si non fourni
 * - le statut initial est EN_ATTENTE
 */
function examen_create(
    int $idDossier,
    string $typeExamen,
    ?string $noteMedecin = null,
    ?int $idMedecin = null
): bool {
    $sql = "
        INSERT INTO examen (
            idDossier,
            idMedecin,
            typeExamen,
            noteMedecin,
            dateDemande,
            statut
        )
        VALUES (
            :idDossier,
            :idMedecin,
            :typeExamen,
            :noteMedecin,
            NOW(),
            :statut
        )
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':idDossier'   => $idDossier,
        ':idMedecin'   => $idMedecin,
        ':typeExamen'  => $typeExamen,
        ':noteMedecin' => $noteMedecin,
        ':statut'      => 'EN_ATTENTE',
    ]);
}

/* =========================================================================
 * LECTURE DES EXAMENS
 * ========================================================================= */

/**
 * Retourne la liste des examens d'un dossier.
 *
 * Tri :
 * - le plus récent en premier
 */
function examens_get_by_dossier(int $idDossier): array
{
    $sql = "
        SELECT
            idExamen,
            idDossier,
            idMedecin,
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
 * Retourne une demande d'examen par son identifiant.
 */
function examen_get_by_id(int $idExamen): ?array
{
    $sql = "
        SELECT
            idExamen,
            idDossier,
            idMedecin,
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
 * Retourne la liste des demandes récentes.
 *
 * Option :
 * - filtrage possible par statut
 */
function examens_get_recent(int $limit = 10, ?string $statut = null): array
{
    $limit = max(1, min($limit, 100));

    if ($statut !== null) {
        $sql = "
            SELECT
                idExamen,
                idDossier,
                idMedecin,
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
            idMedecin,
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
 * Retourne la liste récente des examens avec les informations patient.
 *
 * Utilisation :
 * - affichage dashboard
 * - affichage nom/prénom + type + statut
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
 * Retourne la liste des examens en attente avec les informations patient.
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

/**
 * Retourne la liste des types d'examens.
 *
 * Utilisation :
 * - formulaire de création de demande
 */
function examens_types_all(): array
{
    $sql = "
        SELECT idType, libelle
        FROM type_examen
        ORDER BY libelle ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================================================================
 * MISE À JOUR DES EXAMENS
 * ========================================================================= */

/**
 * Met à jour le statut d'un examen.
 *
 * Statuts autorisés :
 * - EN_ATTENTE
 * - EN_COURS
 * - TERMINE
 * - ANNULE
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
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':statut'   => $statut,
        ':idExamen' => $idExamen,
    ]);
}

/**
 * Enregistre le résultat d'un examen et le marque comme terminé.
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
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':resultat' => $resultat,
        ':statut'   => 'TERMINE',
        ':idExamen' => $idExamen,
    ]);
}

/* =========================================================================
 * STATISTIQUES
 * ========================================================================= */

/**
 * Retourne le nombre d'examens en attente.
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
