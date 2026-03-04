<?php
declare(strict_types=1);

/*
==================================================
 MODEL : TransfertModel
==================================================
 Rôle :
 - Requêtes SQL liées aux transferts inter-hôpitaux.
 - Aucune logique métier / affichage ici.
 - Utilisation des constantes de tables (_tables.php)
   pour éviter les problèmes de majuscules/minuscules
   entre Windows et Linux (Render / Railway).
==================================================
*/

require_once APP_PATH . '/config/database.php';
require_once __DIR__ . '/_tables.php';


/**
 * Créer une demande de transfert (Médecin).
 * statutTransfer par défaut: 'demande'
 */
function transfert_create_patient(
    int $idPatient,
    int $idHopitalSource,
    string $hopitalDestinataire,
    ?string $serviceDestinataire = null
): bool {
    $pdo = db();

    $sql = "
        INSERT INTO " . T_TRANSFER_PATIENT . "
            (idPatient, idHopital, dateCreation, statutTransfer, hopitalDestinataire, serviceDestinataire)
        VALUES
            (:idPatient, :idHopital, NOW(), 'demande', :hopitalDestinataire, :serviceDestinataire)
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idPatient'           => $idPatient,
        ':idHopital'           => $idHopitalSource,
        ':hopitalDestinataire' => $hopitalDestinataire,
        ':serviceDestinataire' => $serviceDestinataire,
    ]);
}


/**
 * Récupérer l'historique des transferts d'un patient.
 */
function transferts_get_by_patient(int $idPatient): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . T_TRANSFER_PATIENT . "
        WHERE idPatient = :idPatient
        ORDER BY dateCreation DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':idPatient' => $idPatient]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Liste des demandes en attente (Directeur).
 */
function transferts_get_pending(): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . T_TRANSFER_PATIENT . "
        WHERE statutTransfer IN ('demande', 'attente_reponse')
        ORDER BY dateCreation DESC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Mettre à jour le statut d'un transfert.
 */
function transfert_update_statut(int $idTransfer, string $newStatut): bool
{
    $pdo = db();

    $sql = "
        UPDATE " . T_TRANSFER_PATIENT . "
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


/**
 * Récupérer un transfert par ID.
 */
function transfert_get_by_id(int $idTransfer): ?array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . T_TRANSFER_PATIENT . "
        WHERE idTransfer = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idTransfer]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}


/**
 * Liste des hôpitaux.
 */
function hopitaux_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT idHopital, nom, ville
        FROM " . T_HOPITAL . "
        ORDER BY nom ASC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Compter les transferts par patient.
 */
function transferts_count_by_patients(array $idsPatients): array
{
    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();

    $in = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT idPatient, COUNT(*) AS nb
        FROM " . T_TRANSFER_PATIENT . "
        WHERE idPatient IN ($in)
        GROUP BY idPatient
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsPatients);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $result[(int)$r['idPatient']] = (int)$r['nb'];
    }

    return $result;
}
