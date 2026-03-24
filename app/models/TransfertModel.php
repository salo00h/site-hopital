<?php
declare(strict_types=1);

/*
==================================================
 MODEL : TransfertModel
==================================================
 Rôle :
 - Contenir uniquement les requêtes SQL liées aux transferts inter-hôpitaux.
 - Aucune logique métier ou affichage ici.
 - Utilisation de PDO avec requêtes préparées.
 - Respect des noms réels des tables en lowercase.
==================================================
*/

require_once APP_PATH . '/config/database.php';

const TRANSFERT_TABLE = 'transfert_patient';

/**
 * Crée une demande de transfert pour un patient.
 * Le statut initial est "demande".
 */
function transfert_create_patient(
    int $idPatient,
    int $idHopitalSource,
    string $hopitalDestinataire,
    ?string $serviceDestinataire = null
): bool {
    $pdo = db();

    $sql = "
        INSERT INTO " . TRANSFERT_TABLE . " (
            idPatient,
            idHopital,
            dateCreation,
            statutTransfer,
            hopitalDestinataire,
            serviceDestinataire
        ) VALUES (
            :idPatient,
            :idHopital,
            NOW(),
            :statutTransfer,
            :hopitalDestinataire,
            :serviceDestinataire
        )
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idPatient' => $idPatient,
        ':idHopital' => $idHopitalSource,
        ':statutTransfer' => 'demande',
        ':hopitalDestinataire' => trim($hopitalDestinataire),
        ':serviceDestinataire' => ($serviceDestinataire !== null && trim($serviceDestinataire) !== '')
            ? trim($serviceDestinataire)
            : null,
    ]);
}

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
 * Retourne la liste des transferts en attente.
 */
function transferts_get_pending(): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE statutTransfer IN (:statut1, :statut2)
        ORDER BY dateCreation DESC, idTransfer DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':statut1' => 'demande',
        ':statut2' => 'attente_reponse',
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Met à jour le statut d'un transfert.
 */
function transfert_update_statut(int $idTransfer, string $newStatut): bool
{
    $pdo = db();

    $sql = "
        UPDATE " . TRANSFERT_TABLE . "
        SET statutTransfer = :statut
        WHERE idTransfer = :id
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':statut' => $newStatut,
        ':id' => $idTransfer,
    ]);
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
 * Retourne la liste des hôpitaux.
 */
function hopitaux_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT idHopital, nom, ville
        FROM hopital
        ORDER BY nom ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Compte le nombre de transferts par patient.
 *
 * @param int[] $idsPatients
 * @return array<int,int>
 */
function transferts_count_by_patients(array $idsPatients): array
{
    $idsPatients = array_values(array_unique(array_map('intval', $idsPatients)));
    $idsPatients = array_values(array_filter($idsPatients, static fn(int $id): bool => $id > 0));

    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT idPatient, COUNT(*) AS nb
        FROM transfert_patient
        WHERE idPatient IN ($placeholders)
        GROUP BY idPatient
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsPatients);

    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int) $row['idPatient']] = (int) $row['nb'];
    }

    return $result;
}

/**
 * Retourne le dernier statut de transfert pour chaque patient.
 *
 * Résultat :
 * [
 *   idPatient => 'demande',
 *   idPatient => 'accepte'
 * ]
 *
 * @param int[] $idsPatients
 * @return array<int,string>
 */
function transferts_last_statut_by_patients(array $idsPatients): array
{
    $idsPatients = array_values(array_unique(array_map('intval', $idsPatients)));
    $idsPatients = array_values(array_filter($idsPatients, static fn(int $id): bool => $id > 0));

    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT t1.idPatient, t1.statutTransfer
        FROM transfert_patient t1
        INNER JOIN (
            SELECT idPatient, MAX(dateCreation) AS maxDate
            FROM transfert_patient
            WHERE idPatient IN ($placeholders)
            GROUP BY idPatient
        ) t2
            ON t1.idPatient = t2.idPatient
           AND t1.dateCreation = t2.maxDate
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsPatients);

    $result = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[(int) $row['idPatient']] = (string) $row['statutTransfer'];
    }

    return $result;
}
