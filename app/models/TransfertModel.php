<?php
declare(strict_types=1);

/*
==================================================
 MODEL : TransfertModel
==================================================
 Rôle :
 - Requêtes SQL liées aux transferts inter-hôpitaux.
 - Aucune logique métier / affichage ici.
==================================================
*/

require_once APP_PATH . '/config/database.php';
require_once __DIR__ . '/_tables.php';
const TRANSFERT_TABLE = 'transfert_patient';

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
        INSERT INTO " . TRANSFERT_TABLE . " 
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
        FROM " . TRANSFERT_TABLE . "
        WHERE idPatient = :idPatient
        ORDER BY dateCreation DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':idPatient' => $idPatient]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Liste des demandes en attente (Directeur).
 * Ici on prend 'demande' et/ou 'attente_reponse' حسب اختيارك.
 */
function transferts_get_pending(): array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE statutTransfer IN ('demande', 'attente_reponse')
        ORDER BY dateCreation DESC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mettre à jour le statut d'un transfert (Directeur).
 * Valeurs possibles: 'accepte' ou 'refuse' (ou 'termine').
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

/**
 * (Optionnel) Récupérer un transfert par ID.
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
    $stmt->execute([':id' => $idTransfer]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}


function hopitaux_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT idHopital, nom, ville
        FROM hopital
        ORDER BY nom ASC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


function transferts_count_by_patients(array $idsPatients): array
{
    if (empty($idsPatients)) {
        return [];
    }

    $pdo = db();

    $in = implode(',', array_fill(0, count($idsPatients), '?'));

    $sql = "
        SELECT idPatient, COUNT(*) AS nb
        FROM transfert_patient
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
