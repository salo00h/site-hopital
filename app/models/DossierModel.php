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
 * - Jointure avec PATIENT
 * - Jointure avec GESTION_LIT + LIT pour afficher le numéro du lit si existant
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
        FROM DOSSIER_PATIENT d
        INNER JOIN PATIENT p ON p.idPatient = d.idPatient
        LEFT JOIN GESTION_LIT gl ON gl.idDossier = d.idDossier
        LEFT JOIN LIT l ON l.idLit = gl.idLit
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
 * Retourne un dossier par son id (ou null si introuvable).
 * On récupère aussi :
 * - les infos patient
 * - le lit (si réservé) via GESTION_LIT
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
        FROM DOSSIER_PATIENT d
        INNER JOIN PATIENT p ON p.idPatient = d.idPatient
        LEFT JOIN GESTION_LIT gl ON gl.idDossier = d.idDossier
        LEFT JOIN LIT l ON l.idLit = gl.idLit
        WHERE d.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retourne le lit lié au dossier (ou null si aucun).
 * Utilisé pour :
 * - empêcher qu'un dossier réserve plusieurs lits
 * - afficher le numéro du lit dans le détail
 */
function getLitForDossier(int $idDossier): ?array
{
    $sql = "
        SELECT l.idLit, l.numeroLit, l.etatLit
        FROM GESTION_LIT gl
        INNER JOIN LIT l ON l.idLit = gl.idLit
        WHERE gl.idDossier = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Crée un dossier (lié à un patient déjà existant) et renvoie l'idDossier.
 * Les champs vides sont enregistrés en NULL.
 */
function createDossier(int $idPatient, array $data): int
{
    $sql = "
        INSERT INTO DOSSIER_PATIENT
            (idPatient, idHopital, dateCreation, dateAdmission, dateSortie,
             historiqueMedical, antecedant, etat_entree, diagnostic, traitements,
             statut, niveau, delaiPriseCharge, idTransfert)
        VALUES
            (:idPatient, :idHopital, :dateCreation, :dateAdmission, :dateSortie,
             :historiqueMedical, :antecedant, :etat_entree, :diagnostic, :traitements,
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
        ':traitements' => toNull($data['traitements'] ?? null),

        ':statut' => $data['statut'] ?? 'ouvert',
        ':niveau' => $data['niveau'] ?? null,
        ':delaiPriseCharge' => $data['delaiPriseCharge'] ?? null,

        ':idTransfert' => (($data['idTransfert'] ?? 0) != 0) ? (int) $data['idTransfert'] : null,
    ]);

    return (int) db()->lastInsertId();
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
        $idPatient = createPatient($patient);
        $idDossier = createDossier($idPatient, $dossier);

        $pdo->commit();
        return $idDossier;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Met à jour un dossier existant.
 * Les champs vides sont enregistrés en NULL.
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
        UPDATE DOSSIER_PATIENT SET
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
 * $limit : nombre maximum de résultats (par défaut 5).
 */
function dossiers_get_recent(int $limit = 5): array
{
    $sql = "
        SELECT
            d.idDossier,
            CONCAT(p.prenom, ' ', p.nom) AS nomComplet
        FROM DOSSIER_PATIENT d
        INNER JOIN PATIENT p ON p.idPatient = d.idPatient
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
 * Retourne le nombre de demandes d'examens par dossier.
 * @param int[] $idsDossiers
 * @return array<int,int> [idDossier => nbExamens]
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

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['idDossier']] = (int)$row['nb'];
    }
    return $map;
}

/**
 * Compte les dossiers selon le statut.
 *
 * On accepte plusieurs variantes pour éviter les écarts d'écriture :
 * - consultation / en consultation / en_consultation
 * - attente / en attente / en_attente / ouvert
 */
function dossiers_count_patients_consultation(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM DOSSIER_PATIENT
        WHERE LOWER(statut) IN ('consultation', 'en consultation', 'en_consultation')
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int)($row['nb'] ?? 0);
}

/**
 * Patients en attente de consultation.
 * On considère ici :
 * - attente
 * - en attente
 * - en_attente
 * - ouvert
 */
function dossiers_count_patients_attente(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM DOSSIER_PATIENT
        WHERE LOWER(statut) IN ('attente', 'en attente', 'en_attente', 'ouvert')
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int)($row['nb'] ?? 0);
}

/**
 * Nombre de dossiers par niveau de priorité.
 */
function dossiers_count_by_niveau(int $niveau): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM DOSSIER_PATIENT
        WHERE niveau = :niveau
    ";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':niveau', $niveau, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return (int)($row['nb'] ?? 0);
}


/**
 * ==================================================
 * MODEL : confirmInstallationPatient
 * ==================================================
 * Rôle :
 * Cette fonction met à jour les données après
 * la confirmation de l’installation du patient.
 *
 * Elle fait deux modifications :
 * 1) le lit passe à l’état "occupe"
 * 2) le dossier passe à l’état "attente_consultation"
 *
 * Note :
 * J’ai légèrement amélioré ce code avec l’aide de l’IA,
 * sans changer sa logique principale.
 * ==================================================
 */
function confirmInstallationPatient(int $idDossier, int $idLit): void
{
    // Connexion à la base de données
    $pdo = db();

    // Début de la transaction :
    // on veut que les 2 mises à jour réussissent ensemble
    $pdo->beginTransaction();

    try {
        // Mise à jour de l'état du lit
        $stmtLit = $pdo->prepare("
            UPDATE LIT
            SET etatLit = :etat
            WHERE idLit = :idLit
        ");
        $stmtLit->execute([
            ':etat' => 'occupe',
            ':idLit' => $idLit,
        ]);

        // Mise à jour du statut du dossier patient
        $stmtDossier = $pdo->prepare("
            UPDATE DOSSIER_PATIENT
            SET statut = :statut
            WHERE idDossier = :idDossier
        ");
        $stmtDossier->execute([
            ':statut' => 'attente_consultation',
            ':idDossier' => $idDossier,
        ]);

        // Validation définitive des deux requêtes
        $pdo->commit();
    } catch (Throwable $e) {
        // En cas d'erreur :
        // annuler toutes les modifications pour garder des données cohérentes
        $pdo->rollBack();

        // Relancer l'erreur pour qu'elle soit gérée plus haut
        throw $e;
    }
}


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
        throw new InvalidArgumentException("Statut dossier invalide.");
    }

    $sql = "
        UPDATE DOSSIER_PATIENT
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
 * Liste des consultations à venir pour le médecin.
 */
function dossiers_get_consultations(): array
{
    $sql = "
        SELECT
            d.idDossier,
            p.nom,
            p.prenom,
            d.niveau AS priorite
        FROM DOSSIER_PATIENT d
        INNER JOIN PATIENT p ON p.idPatient = d.idPatient
        WHERE LOWER(d.statut) IN ('attente_consultation', 'attente consultation')
        ORDER BY d.niveau DESC, d.idDossier DESC
        LIMIT 10
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}






// ==============================
// VALIDER SORTIE (MEDECIN)
// ==============================
function validerSortieMedicale(int $idDossier): bool
{
    $sql = "UPDATE dossier_patient
            SET sortieValidee = 1,
                dateValidationSortie = NOW()
            WHERE idDossier = :idDossier
              AND statut <> 'ferme'";

    $stmt = db()->prepare($sql);
    return $stmt->execute([
        ':idDossier' => $idDossier
    ]);
}



// ==============================
// CONFIRMER SORTIE (INFIRMIER)
// ==============================
function confirmerSortieFinale(int $idDossier): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Vérifier que la sortie a déjà été validée par le médecin
        $stmt = $pdo->prepare("
            SELECT sortieValidee
            FROM dossier_patient
            WHERE idDossier = :id
        ");
        $stmt->execute([':id' => $idDossier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)($row['sortieValidee'] ?? 0) !== 1) {
            throw new Exception("Sortie non validée par le médecin.");
        }

        // 1) Fermer le dossier
        $stmt = $pdo->prepare("
            UPDATE dossier_patient
            SET statut = 'ferme',
                dateSortie = NOW(),
                sortieConfirmee = 1
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([':idDossier' => $idDossier]);

        // 2) Récupérer le lit lié au dossier
        $stmt = $pdo->prepare("
            SELECT idLit
            FROM gestion_lit
            WHERE idDossier = :idDossier
            LIMIT 1
        ");
        $stmt->execute([':idDossier' => $idDossier]);
        $lit = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3) Libérer le lit
        if ($lit && !empty($lit['idLit'])) {
            $stmt = $pdo->prepare("
                UPDATE lit
                SET etatLit = 'disponible'
                WHERE idLit = :idLit
            ");
            $stmt->execute([':idLit' => (int)$lit['idLit']]);
        }

        // 4) Récupérer les équipements liés au dossier
        $stmt = $pdo->prepare("
            SELECT idEquipement
            FROM gestion_equipement
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([':idDossier' => $idDossier]);
        $equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5) Libérer tous les équipements
        if (!empty($equipements)) {
            $stmtEq = $pdo->prepare("
                UPDATE equipement
                SET etatEquipement = 'disponible'
                WHERE idEquipement = :idEquipement
            ");

            foreach ($equipements as $eq) {
                $stmtEq->execute([
                    ':idEquipement' => (int)$eq['idEquipement']
                ]);
            }
        }

        // 6) Conserver les liens pour l'historique
        // On garde les relations dans gestion_lit et gestion_equipement.
        // Ainsi, le dossier fermé garde la trace du lit et des équipements utilisés.

        $pdo->commit();
        return true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

