<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MODEL : TransfertModel
|--------------------------------------------------------------------------
| Rôle :
| - Gérer les requêtes SQL liées aux transferts inter-hôpitaux
| - Ne contenir aucune logique métier ni affichage
| - Centraliser l'accès aux données de transfert
| - Utiliser les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/config/database.php';

const TRANSFERT_TABLE = 'transfert_patient';

/* =========================================================================
 * CRÉATION DES TRANSFERTS
 * ========================================================================= */

/**
 * Crée une demande de transfert patient.
 *
 * Pourquoi cette structure ?
 * - typeTransfer permet de distinguer un transfert interne ou externe
 * - validationDirecteur permet de conserver la validation du directeur
 *   lorsqu'elle est nécessaire
 *
 * Avantage :
 * - la demande de transfert est enregistrée avec toutes les informations utiles
 * - la base reste cohérente avec les colonnes actuelles
 */
function transfert_create_patient(
    int $idPatient,
    int $idHopitalSource,
    string $hopitalDestinataire,
    ?string $serviceDestinataire = null,
    string $statut = 'demande',
    string $typeTransfer = 'interne',
    ?string $validationDirecteur = null
): bool {
    $pdo = db();

    $sql = "
        INSERT INTO " . TRANSFERT_TABLE . "
        (
            idPatient,
            idHopital,
            dateCreation,
            statutTransfer,
            hopitalDestinataire,
            serviceDestinataire,
            typeTransfer,
            validationDirecteur
        )
        VALUES
        (
            :idPatient,
            :idHopital,
            NOW(),
            :statut,
            :hopitalDestinataire,
            :serviceDestinataire,
            :typeTransfer,
            :validationDirecteur
        )
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idPatient'           => $idPatient,
        ':idHopital'           => $idHopitalSource,
        ':statut'              => $statut,
        ':hopitalDestinataire' => $hopitalDestinataire,
        ':serviceDestinataire' => $serviceDestinataire,
        ':typeTransfer'        => $typeTransfer,
        ':validationDirecteur' => $validationDirecteur,
    ]);
}

/* =========================================================================
 * LECTURE DES TRANSFERTS
 * ========================================================================= */

/**
 * Retourne l'historique des transferts d'un patient.
 */
function transferts_get_by_patient(int $idPatient): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE idPatient = :idPatient
        ORDER BY dateCreation DESC, idTransfer DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':idPatient' => $idPatient,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne la liste des demandes de transfert en attente.
 *
 * Utilisation :
 * - dashboard directeur
 * - affichage des demandes à valider
 */
function transferts_get_pending(): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE statutTransfer = 'demande'
        ORDER BY dateCreation DESC, idTransfer DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un transfert par son identifiant.
 */
function transfert_get_by_id(int $idTransfer): ?array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE idTransfer = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $idTransfer,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Retourne les transferts récents.
 *
 * Utilisation :
 * - dashboard directeur
 */
function transferts_get_recent(int $limit = 5): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        ORDER BY dateCreation DESC, idTransfer DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================================================================
 * MISE À JOUR DES TRANSFERTS
 * ========================================================================= */

/**
 * Met à jour le statut d'un transfert.
 *
 * Exemples de statuts possibles :
 * - accepte
 * - refuse
 * - termine
 */
function transfert_update_statut(int $idTransfer, string $newStatut): bool
{
    $pdo = db();

    $sql = "
        UPDATE " . TRANSFERT_TABLE . "
        SET statutTransfer = :statut
        WHERE idTransfer = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':statut' => $newStatut,
        ':id'     => $idTransfer,
    ]);
}

/* =========================================================================
 * DONNÉES COMPLÉMENTAIRES
 * ========================================================================= */

/**
 * Retourne la liste des hôpitaux.
 */
function hopitaux_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT
            idHopital,
            nom,
            ville
        FROM hopital
        ORDER BY nom ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================================================================
 * STATISTIQUES ET AGRÉGATIONS
 * ========================================================================= */

/**
 * Compte le nombre de transferts par patient.
 *
 * Résultat attendu :
 * [
 *   idPatient => nbTransferts,
 *   ...
 * ]
 */
function transferts_count_by_patients(array $idsPatients): array
{
    $idsPatients = array_values(array_unique(array_map('intval', $idsPatients)));
    $idsPatients = array_values(array_filter(
        $idsPatients,
        static fn(int $id): bool => $id > 0
    ));

    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();
    $in = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT
            idPatient,
            COUNT(*) AS nb
        FROM transfert_patient
        WHERE idPatient IN ($in)
        GROUP BY idPatient
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsPatients);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $result = [];

    foreach ($rows as $row) {
        $result[(int) $row['idPatient']] = (int) $row['nb'];
    }

    return $result;
}

/**
 * Retourne le dernier statut de transfert pour chaque patient.
 *
 * Résultat attendu :
 * [
 *   idPatient => 'demande',
 *   idPatient => 'accepte',
 *   ...
 * ]
 */
function transferts_last_statut_by_patients(array $idsPatients): array
{
    $idsPatients = array_values(array_unique(array_map('intval', $idsPatients)));
    $idsPatients = array_values(array_filter(
        $idsPatients,
        static fn(int $id): bool => $id > 0
    ));

    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();
    $in = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT
            t1.idPatient,
            t1.statutTransfer
        FROM transfert_patient t1
        INNER JOIN (
            SELECT
                idPatient,
                MAX(dateCreation) AS maxDate
            FROM transfert_patient
            WHERE idPatient IN ($in)
            GROUP BY idPatient
        ) t2
            ON t1.idPatient = t2.idPatient
           AND t1.dateCreation = t2.maxDate
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsPatients);

    $result = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $result[(int) $row['idPatient']] = (string) $row['statutTransfer'];
    }

    return $result;
}
