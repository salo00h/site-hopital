<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DOSSIER MODEL
|--------------------------------------------------------------------------
| Rôle :
| - Centraliser les accès aux données du dossier patient.
| - Utiliser uniquement PDO avec des requêtes préparées.
| - Respecter les noms réels des tables en lowercase.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PatientModel.php';

/**
 * Transforme une valeur vide en NULL.
 */
function toNull(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    return $value;
}

/**
 * Retourne la liste des dossiers avec recherche optionnelle.
 * Jointure avec patient et lit si un lit est lié au dossier.
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
            d.sortieValidee,
            d.sortieConfirmee,
            l.idLit,
            l.numeroLit,
            p.idPatient,
            p.nom,
            p.prenom,
            p.dateNaissance,
            p.genre
        FROM dossier_patient d
        INNER JOIN patient p ON p.idPatient = d.idPatient
        LEFT JOIN gestion_lit gl ON gl.idDossier = d.idDossier
        LEFT JOIN lit l ON l.idLit = gl.idLit
        WHERE 1 = 1
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

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un dossier par son identifiant.
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
        FROM dossier_patient d
        INNER JOIN patient p ON p.idPatient = d.idPatient
        LEFT JOIN gestion_lit gl ON gl.idDossier = d.idDossier
        LEFT JOIN lit l ON l.idLit = gl.idLit
        WHERE d.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Retourne le lit associé à un dossier.
 */
function getLitForDossier(int $idDossier): ?array
{
    $sql = "
        SELECT
            l.idLit,
            l.numeroLit,
            l.etatLit
        FROM gestion_lit gl
        INNER JOIN lit l ON l.idLit = gl.idLit
        WHERE gl.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Crée un dossier lié à un patient existant.
 * Retourne l'identifiant du nouveau dossier.
 */
function createDossier(int $idPatient, array $data): int
{
    $sql = "
        INSERT INTO dossier_patient (
            idPatient,
            idHopital,
            dateCreation,
            dateAdmission,
            dateSortie,
            historiqueMedical,
            antecedant,
            etat_entree,
            diagnostic,
            traitements,
            statut,
            niveau,
            delaiPriseCharge,
            idTransfert
        ) VALUES (
            :idPatient,
            :idHopital,
            :dateCreation,
            :dateAdmission,
            :dateSortie,
            :historiqueMedical,
            :antecedant,
            :etat_entree,
            :diagnostic,
            :traitements,
            :statut,
            :niveau,
            :delaiPriseCharge,
            :idTransfert
        )
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idPatient' => $idPatient,
        ':idHopital' => toNull($data['idHopital'] ?? null),
        ':dateCreation' => date('Y-m-d'),
        ':dateAdmission' => toNull($data['dateAdmission'] ?? null),
        ':dateSortie' => toNull($data['dateSortie'] ?? null),
        ':historiqueMedical' => toNull($data['historiqueMedical'] ?? null),
        ':antecedant' => toNull($data['antecedant'] ?? null),
        ':etat_entree' => toNull($data['etat_entree'] ?? null),
        ':diagnostic' => toNull($data['diagnostic'] ?? null),
        ':traitements' => toNull($data['traitements'] ?? null),
        ':statut' => toNull($data['statut'] ?? 'ouvert'),
        ':niveau' => toNull($data['niveau'] ?? null),
        ':delaiPriseCharge' => toNull($data['delaiPriseCharge'] ?? null),
        ':idTransfert' => (($data['idTransfert'] ?? 0) != 0) ? (int) $data['idTransfert'] : null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Crée un patient puis son dossier dans une transaction.
 */
function createPatientAndDossier(array $patient, array $dossier): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $idPatient = createPatient($patient);
        $idDossier = createDossier($idPatient, $dossier);

        $pdo->commit();
        return $idDossier;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Met à jour un dossier existant.
 */
function updateDossier(
    int $idDossier,
    ?string $dateAdmission,
    ?string $dateSortie,
    ?string $historiqueMedical,
    ?string $antecedant,
    ?string $etat_entree,
    ?string $diagnostic,
    ?string $traitements,
    string $statut,
    string $niveau,
    string $delai
): void {
    $sql = "
        UPDATE dossier_patient
        SET
            dateAdmission = :dateAdmission,
            dateSortie = :dateSortie,
            historiqueMedical = :historiqueMedical,
            antecedant = :antecedant,
            etat_entree = :etat_entree,
            diagnostic = :diagnostic,
            traitements = :traitements,
            statut = :statut,
            niveau = :niveau,
            delaiPriseCharge = :delai
        WHERE idDossier = :idDossier
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':dateAdmission' => toNull($dateAdmission),
        ':dateSortie' => toNull($dateSortie),
        ':historiqueMedical' => toNull($historiqueMedical),
        ':antecedant' => toNull($antecedant),
        ':etat_entree' => toNull($etat_entree),
        ':diagnostic' => toNull($diagnostic),
        ':traitements' => toNull($traitements),
        ':statut' => $statut,
        ':niveau' => $niveau,
        ':delai' => $delai,
        ':idDossier' => $idDossier,
    ]);
}

/**
 * Retourne les dossiers les plus récents.
 */
function dossiers_get_recent(int $limit = 5): array
{
    $sql = "
        SELECT
            d.idDossier,
            CONCAT(p.prenom, ' ', p.nom) AS nomComplet
        FROM dossier_patient d
        INNER JOIN patient p ON p.idPatient = d.idPatient
        ORDER BY
            (d.dateAdmission IS NULL) ASC,
            d.dateAdmission DESC,
            d.idDossier DESC
        LIMIT :lim
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne le nombre d'examens par dossier.
 *
 * @param int[] $idsDossiers
 * @return array<int,int>
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
        FROM examen
        WHERE idDossier IN ($placeholders)
        GROUP BY idDossier
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($idsDossiers);

    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int) $row['idDossier']] = (int) $row['nb'];
    }

    return $result;
}

/**
 * Compte les patients en consultation.
 */
function dossiers_count_patients_consultation(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM dossier_patient
        WHERE LOWER(statut) IN ('consultation', 'en consultation', 'en_consultation')
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int) ($row['nb'] ?? 0);
}

/**
 * Compte les patients en attente.
 */
function dossiers_count_patients_attente(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM dossier_patient
        WHERE LOWER(statut) IN ('attente', 'en attente', 'en_attente', 'ouvert')
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int) ($row['nb'] ?? 0);
}

/**
 * Compte les dossiers par niveau.
 */
function dossiers_count_by_niveau(int $niveau): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM dossier_patient
        WHERE niveau = :niveau
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':niveau', $niveau, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int) ($row['nb'] ?? 0);
}

/**
 * Confirme l'installation du patient :
 * - le lit passe à l'état occupe
 * - le dossier passe à l'état attente_consultation
 */
function confirmInstallationPatient(int $idDossier, int $idLit): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmtLit = $pdo->prepare("
            UPDATE lit
            SET etatLit = :etat
            WHERE idLit = :idLit
        ");
        $stmtLit->execute([
            ':etat' => 'occupe',
            ':idLit' => $idLit,
        ]);

        $stmtDossier = $pdo->prepare("
            UPDATE dossier_patient
            SET statut = :statut
            WHERE idDossier = :idDossier
        ");
        $stmtDossier->execute([
            ':statut' => 'attente_consultation',
            ':idDossier' => $idDossier,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Met à jour le statut d'un dossier.
 */
function dossier_update_statut(int $idDossier, string $statut): void
{
    $allowed = [
        'ouvert',
        'attente_consultation',
        'consultation',
        'attente_examen',
        'attente_resultat',
        'transfert',
        'ferme',
    ];

    if (!in_array($statut, $allowed, true)) {
        throw new InvalidArgumentException('Statut dossier invalide.');
    }

    $sql = "
        UPDATE dossier_patient
        SET statut = :statut
        WHERE idDossier = :idDossier
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':statut' => $statut,
        ':idDossier' => $idDossier,
    ]);
}

/**
 * Liste des consultations à venir.
 */
function dossiers_get_consultations(): array
{
    $sql = "
        SELECT
            d.idDossier,
            p.nom,
            p.prenom,
            d.niveau AS priorite
        FROM dossier_patient d
        INNER JOIN patient p ON p.idPatient = d.idPatient
        WHERE LOWER(d.statut) IN ('attente_consultation', 'attente consultation')
        ORDER BY d.niveau DESC, d.idDossier DESC
        LIMIT 10
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Valide la sortie médicale.
 */
function validerSortieMedicale(int $idDossier): bool
{
    $sql = "
        UPDATE dossier_patient
        SET
            sortieValidee = 1,
            dateValidationSortie = NOW()
        WHERE idDossier = :idDossier
          AND statut <> 'ferme'
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':idDossier' => $idDossier,
    ]);
}

/**
 * Confirme la sortie finale du patient.
 * - ferme le dossier
 * - libère le lit
 * - libère les équipements
 * - conserve les relations pour l'historique
 */
function confirmerSortieFinale(int $idDossier): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            SELECT sortieValidee
            FROM dossier_patient
            WHERE idDossier = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $idDossier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int) ($row['sortieValidee'] ?? 0) !== 1) {
            throw new Exception('Sortie non validée par le médecin.');
        }

        $stmt = $pdo->prepare("
            UPDATE dossier_patient
            SET
                statut = 'ferme',
                dateSortie = NOW(),
                sortieConfirmee = 1
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([':idDossier' => $idDossier]);

        $stmt = $pdo->prepare("
            SELECT idLit
            FROM gestion_lit
            WHERE idDossier = :idDossier
            LIMIT 1
        ");
        $stmt->execute([':idDossier' => $idDossier]);
        $lit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lit && !empty($lit['idLit'])) {
            $stmt = $pdo->prepare("
                UPDATE lit
                SET etatLit = 'disponible'
                WHERE idLit = :idLit
            ");
            $stmt->execute([
                ':idLit' => (int) $lit['idLit'],
            ]);
        }

        $stmt = $pdo->prepare("
            SELECT idEquipement
            FROM gestion_equipement
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([':idDossier' => $idDossier]);
        $equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($equipements)) {
            $stmtEq = $pdo->prepare("
                UPDATE equipement
                SET etatEquipement = 'disponible'
                WHERE idEquipement = :idEquipement
            ");

            foreach ($equipements as $eq) {
                $stmtEq->execute([
                    ':idEquipement' => (int) $eq['idEquipement'],
                ]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
