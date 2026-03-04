<?php
declare(strict_types=1);

/*
  ==============================
  DOSSIER MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Regrouper uniquement l'accès aux données (SQL).
  - Le contrôleur appelle ces fonctions.
  - PDO + requêtes préparées (protection contre l'injection SQL).
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PatientModel.php';
require_once __DIR__ . '/_tables.php';

/**
 * Convertit une valeur en NULL si vide (string vide).
 */
function toNull(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $value = trim($value);
        return ($value === '') ? null : $value;
    }
    return $value;
}

/**
 * Retourne la liste des dossiers (avec recherche optionnelle).
 */
function getAllDossiers(string $q = ''): array
{
    $sql = "
        SELECT
            d.idDossier,
            d.dateAdmission,
            d.statut,
            d.niveau,
            d.delaiPriseCharge,
            l.idLit,
            l.numeroLit,
            p.idPatient,
            p.nom,
            p.prenom,
            p.dateNaissance,
            p.genre
        FROM " . T_DOSSIER . " d
        INNER JOIN " . T_PATIENT . " p ON p.idPatient = d.idPatient
        LEFT JOIN " . T_GESTION_LIT . " gl ON gl.idDossier = d.idDossier
        LEFT JOIN " . T_LIT . " l ON l.idLit = gl.idLit
        WHERE 1=1
    ";

    $params = [];

    if ($q !== '') {
        $sql .= " AND (p.nom LIKE :q OR p.prenom LIKE :q";

        $params[':q'] = '%' . $q . '%';

        if (ctype_digit($q)) {
            $sql .= " OR d.idDossier = :idq";
            $params[':idq'] = (int) $q;
        }

        $sql .= ")";
    }

    $sql .= " ORDER BY d.idDossier DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

/**
 * Retourne un dossier par son id.
 */
function getDossierById(int $idDossier): ?array
{
    $sql = "
        SELECT
            d.*,
            l.idLit,
            l.numeroLit,
            l.etatLit,
            p.nom,
            p.prenom,
            p.dateNaissance,
            p.adresse,
            p.telephone,
            p.email,
            p.genre,
            p.numeroCarteVitale,
            p.mutuelle
        FROM " . T_DOSSIER . " d
        INNER JOIN " . T_PATIENT . " p ON p.idPatient = d.idPatient
        LEFT JOIN " . T_GESTION_LIT . " gl ON gl.idDossier = d.idDossier
        LEFT JOIN " . T_LIT . " l ON l.idLit = gl.idLit
        WHERE d.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retourne le lit lié au dossier.
 */
function getLitForDossier(int $idDossier): ?array
{
    $sql = "
        SELECT l.idLit, l.numeroLit, l.etatLit
        FROM " . T_GESTION_LIT . " gl
        INNER JOIN " . T_LIT . " l ON l.idLit = gl.idLit
        WHERE gl.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Crée un dossier.
 */
function createDossier(int $idPatient, array $data): int
{
    $sql = "
        INSERT INTO " . T_DOSSIER . "
            (idPatient, idHopital, dateCreation, dateAdmission, dateSortie,
             historiqueMedical, antecedant, etat_entree, diagnostic, examen, traitements,
             statut, niveau, delaiPriseCharge, idTransfert)
        VALUES
            (:idPatient, :idHopital, :dateCreation, :dateAdmission, :dateSortie,
             :historiqueMedical, :antecedant, :etat_entree, :diagnostic, :examen, :traitements,
             :statut, :niveau, :delaiPriseCharge, :idTransfert)
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idPatient' => $idPatient,
        ':idHopital' => $data['idHopital'] ?? null,
        ':dateCreation' => date('Y-m-d'),
        ':dateAdmission' => toNull($data['dateAdmission'] ?? null),
        ':dateSortie' => toNull($data['dateSortie'] ?? null),
        ':historiqueMedical' => toNull($data['historiqueMedical'] ?? null),
        ':antecedant' => toNull($data['antecedant'] ?? null),
        ':etat_entree' => toNull($data['etat_entree'] ?? null),
        ':diagnostic' => toNull($data['diagnostic'] ?? null),
        ':examen' => toNull($data['examen'] ?? null),
        ':traitements' => toNull($data['traitements'] ?? null),
        ':statut' => $data['statut'] ?? null,
        ':niveau' => $data['niveau'] ?? null,
        ':delaiPriseCharge' => $data['delaiPriseCharge'] ?? null,
        ':idTransfert' => (($data['idTransfert'] ?? 0) != 0) ? (int) $data['idTransfert'] : null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Retourne les dossiers récents.
 */
function dossiers_get_recent(int $limit = 5): array
{
    $sql = "
        SELECT
            d.idDossier,
            CONCAT(p.prenom, ' ', p.nom) AS nomComplet
        FROM " . T_DOSSIER . " d
        INNER JOIN " . T_PATIENT . " p ON p.idPatient = d.idPatient
        ORDER BY
            (d.dateAdmission IS NULL) ASC,
            d.dateAdmission DESC,
            d.idDossier DESC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

/**
 * Retourne le nombre d'examens par dossier.
 */
function examens_count_by_dossiers(array $idsDossiers): array
{
    $idsDossiers = array_values(array_unique(array_map('intval', $idsDossiers)));
    $idsDossiers = array_values(array_filter($idsDossiers, static fn(int $id): bool => $id > 0));

    if (empty($idsDossiers)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT idDossier, COUNT(*) AS nb
        FROM " . T_EXAMEN . "
        WHERE idDossier IN ($placeholders)
        GROUP BY idDossier
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($idsDossiers);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['idDossier']] = (int)$row['nb'];
    }

    return $map;
}



/**
 * Crée un patient puis un dossier dans une transaction.
 * En cas d'erreur : rollback.
 */
function createPatientAndDossier(array $patient, array $dossier): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Créer le patient
        $idPatient = createPatient($patient);

        // 2) Créer le dossier lié au patient
        $idDossier = createDossier($idPatient, $dossier);

        $pdo->commit();
        return $idDossier;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
