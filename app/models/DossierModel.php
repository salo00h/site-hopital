<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DOSSIER MODEL (PDO / MySQL)
|--------------------------------------------------------------------------
| Rôle :
| - Centraliser l'accès aux données du dossier patient
| - Fournir des fonctions SQL réutilisables par les contrôleurs
| - Utiliser PDO et les requêtes préparées pour sécuriser les accès
| - Respecter les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PatientModel.php';

/* =========================================================================
 * OUTILS COMMUNS
 * ========================================================================= */

/**
 * Convertit une valeur vide en NULL.
 *
 * Comportement :
 * - null reste null
 * - string vide ou composée d'espaces devient null
 * - toute autre valeur est retournée telle quelle
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

/* =========================================================================
 * LECTURE DES DOSSIERS
 * ========================================================================= */

/**
 * Retourne la liste des dossiers avec recherche optionnelle.
 *
 * Données récupérées :
 * - informations du dossier
 * - informations du patient
 * - lit lié au dossier si disponible
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

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un dossier par son identifiant.
 *
 * Données récupérées :
 * - toutes les colonnes du dossier
 * - informations patient
 * - lit associé si existant
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
    $stmt->execute([
        ':id' => $idDossier,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Retourne le lit associé à un dossier.
 *
 * Utilité :
 * - éviter plusieurs réservations de lit pour un même dossier
 * - afficher les informations de lit dans les vues
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
    $stmt->execute([
        ':id' => $idDossier,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Retourne les dossiers les plus récents.
 *
 * @param int $limit Nombre maximum de résultats
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
    $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne le nombre de demandes d'examens par dossier.
 *
 * @param int[] $idsDossiers
 * @return array<int,int> Tableau [idDossier => nbExamens]
 */
function examens_count_by_dossiers(array $idsDossiers): array
{
    $idsDossiers = array_values(array_unique(array_map('intval', $idsDossiers)));
    $idsDossiers = array_values(array_filter(
        $idsDossiers,
        static fn(int $id): bool => $id > 0
    ));

    if (empty($idsDossiers)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT
            idDossier,
            COUNT(*) AS nb
        FROM examen
        WHERE idDossier IN ($placeholders)
        GROUP BY idDossier
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($idsDossiers);

    $map = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(int) $row['idDossier']] = (int) $row['nb'];
    }

    return $map;
}

/* =========================================================================
 * CRÉATION ET MISE À JOUR DES DOSSIERS
 * ========================================================================= */

/**
 * Crée un dossier lié à un patient existant.
 *
 * Les champs vides sont convertis en NULL.
 * L'identifiant du dossier créé est retourné.
 */
function createDossier(int $idPatient, array $data): int
{
    $sql = "
        INSERT INTO dossier_patient
        (
            idPatient,
            idHopital,
            idInfirmierAccueil,
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
        )
        VALUES
        (
            :idPatient,
            :idHopital,
            :idInfirmierAccueil,
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
        ':idPatient'          => $idPatient,
        ':idHopital'          => $data['idHopital'] ?? null,
        ':idInfirmierAccueil' => $data['idInfirmierAccueil'] ?? null,
        ':dateCreation'       => date('Y-m-d'),
        ':dateAdmission'      => toNull($data['dateAdmission'] ?? null),
        ':dateSortie'         => toNull($data['dateSortie'] ?? null),
        ':historiqueMedical'  => toNull($data['historiqueMedical'] ?? null),
        ':antecedant'         => toNull($data['antecedant'] ?? null),
        ':etat_entree'        => toNull($data['etat_entree'] ?? null),
        ':diagnostic'         => toNull($data['diagnostic'] ?? null),
        ':traitements'        => toNull($data['traitements'] ?? null),
        ':statut'             => $data['statut'] ?? 'ouvert',
        ':niveau'             => toNull($data['niveau'] ?? null),
        ':delaiPriseCharge'   => toNull($data['delaiPriseCharge'] ?? null),
        ':idTransfert'        => (($data['idTransfert'] ?? 0) != 0)
            ? (int) $data['idTransfert']
            : null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Crée un patient puis son dossier dans une transaction.
 *
 * En cas d'échec, toute l'opération est annulée.
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
 *
 * Les champs vides sont convertis en NULL avant enregistrement.
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
        ':dateAdmission'     => toNull($dateAdmission),
        ':dateSortie'        => toNull($dateSortie),
        ':historiqueMedical' => toNull($historiqueMedical),
        ':antecedant'        => toNull($antecedant),
        ':etat_entree'       => toNull($etat_entree),
        ':diagnostic'        => toNull($diagnostic),
        ':traitements'       => toNull($traitements),
        ':statut'            => $statut,
        ':niveau'            => $niveau,
        ':delai'             => $delai,
        ':idDossier'         => $idDossier,
    ]);
}

/**
 * Met à jour simplement le statut d'un dossier.
 *
 * Pourquoi cette fonction ?
 * - Garantir une mise à jour centralisée du statut
 * - Contrôler les valeurs autorisées
 * - Améliorer la lisibilité du type de transfert demandé
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
        'transfert_interne',
        'transfert_externe',
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
        ':statut'    => $statut,
        ':idDossier' => $idDossier,
    ]);
}

/* =========================================================================
 * STATISTIQUES GÉNÉRALES
 * ========================================================================= */

/**
 * Compte les dossiers actuellement en consultation.
 *
 * Plusieurs variantes sont acceptées afin de tolérer
 * les différences d'écriture dans les données.
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
 * Compte les dossiers en attente.
 *
 * Les variantes suivantes sont considérées comme attente :
 * - attente
 * - en attente
 * - en_attente
 * - ouvert
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
 * Retourne le nombre de dossiers pour un niveau de priorité donné.
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

/* =========================================================================
 * PARTIE MÉDECIN
 * ========================================================================= */

/**
 * Liste les consultations à venir pour le médecin.
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
 * Valide médicalement la sortie d'un patient.
 *
 * Effet :
 * - active la validation médicale
 * - enregistre la date de validation
 * - ne ferme pas encore le dossier
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

/* =========================================================================
 * PARTIE INFIRMIER
 * ========================================================================= */

/**
 * Confirme l’installation du patient.
 *
 * Actions réalisées dans une transaction :
 * 1) le lit passe à l’état "occupe"
 * 2) le dossier passe à "attente_consultation"
 * 3) l’infirmier ayant confirmé l’installation est tracé
 */
function confirmInstallationPatient(int $idDossier, int $idLit, ?int $idInfirmier): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Mise à jour de l’état du lit
        $stmtLit = $pdo->prepare("
            UPDATE lit
            SET etatLit = :etat
            WHERE idLit = :idLit
        ");
        $stmtLit->execute([
            ':etat'  => 'occupe',
            ':idLit' => $idLit,
        ]);

        // 2) Mise à jour du statut du dossier
        $stmtDossier = $pdo->prepare("
            UPDATE dossier_patient
            SET statut = :statut
            WHERE idDossier = :idDossier
        ");
        $stmtDossier->execute([
            ':statut'    => 'attente_consultation',
            ':idDossier' => $idDossier,
        ]);

        // 3) Traçabilité de l’infirmier d’installation
        $stmtTrace = $pdo->prepare("
            UPDATE gestion_lit
            SET
                idInfirmierInstallation = :idInfirmier,
                dateInstallation = NOW()
            WHERE idDossier = :idDossier
              AND idLit = :idLit
        ");
        $stmtTrace->execute([
            ':idInfirmier' => $idInfirmier,
            ':idDossier'   => $idDossier,
            ':idLit'       => $idLit,
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
 * Confirme définitivement la sortie du patient.
 *
 * Étapes :
 * 1) vérifier que le médecin a validé la sortie
 * 2) fermer le dossier
 * 3) libérer le lit lié au dossier
 * 4) libérer les équipements liés au dossier
 * 5) conserver les liaisons historiques dans les tables de gestion
 */
function confirmerSortieFinale(int $idDossier): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Vérification préalable : la sortie doit déjà être validée médicalement
        $stmt = $pdo->prepare("
            SELECT sortieValidee
            FROM dossier_patient
            WHERE idDossier = :id
        ");
        $stmt->execute([
            ':id' => $idDossier,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int) ($row['sortieValidee'] ?? 0) !== 1) {
            throw new Exception('Sortie non validée par le médecin.');
        }

        // 1) Fermeture du dossier
        $stmt = $pdo->prepare("
            UPDATE dossier_patient
            SET
                statut = 'ferme',
                dateSortie = NOW(),
                sortieConfirmee = 1
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([
            ':idDossier' => $idDossier,
        ]);

        // 2) Récupération du lit associé
        $stmt = $pdo->prepare("
            SELECT idLit
            FROM gestion_lit
            WHERE idDossier = :idDossier
            LIMIT 1
        ");
        $stmt->execute([
            ':idDossier' => $idDossier,
        ]);

        $lit = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3) Libération du lit si un lit est lié au dossier
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

        // 4) Récupération des équipements associés au dossier
        $stmt = $pdo->prepare("
            SELECT idEquipement
            FROM gestion_equipement
            WHERE idDossier = :idDossier
        ");
        $stmt->execute([
            ':idDossier' => $idDossier,
        ]);

        $equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5) Libération de tous les équipements liés
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

        // 6) Conservation de l’historique
        // On garde les relations dans gestion_lit et gestion_equipement
        // afin de préserver la traçabilité après fermeture du dossier.

        $pdo->commit();

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
