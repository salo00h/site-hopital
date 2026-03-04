<?php
declare(strict_types=1);

/*
  ==============================
  LIT MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Accès aux données uniquement (SQL).
  - PDO + requêtes préparées.
  - Utilisation des constantes de tables (_tables.php)
    pour éviter les problèmes de majuscules/minuscules
    entre Windows et Linux (Render / Railway).
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_tables.php';

/**
 * Retourne une valeur entière d'un champ (ou null).
 */
function fetchIntOrNull(string $sql, array $params = [], string $field = ''): ?int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    if ($field === '') {
        $value = array_values($row)[0] ?? null;
    } else {
        $value = $row[$field] ?? null;
    }

    return ($value === null) ? null : (int) $value;
}

/**
 * Retourne l'idService d'un personnel.
 */
function getServiceIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idService FROM " . T_PERSONNEL . " WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idService'
    );
}

/**
 * Retourne l'idInfirmier lié à un personnel.
 */
function getInfirmierIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idInfirmier FROM " . T_INFIRMIER . " WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idInfirmier'
    );
}

/**
 * Statistiques des lits par état.
 */
function getLitStatsByService(int $idService): array
{
    $sql = "
        SELECT etatLit, COUNT(*) AS nb
        FROM " . T_LIT . "
        WHERE idService = ?
        GROUP BY etatLit
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne tous les lits d'un service.
 */
function getLitsByService(int $idService): array
{
    $sql = "
        SELECT idLit, numeroLit, etatLit
        FROM " . T_LIT . "
        WHERE idService = ?
        ORDER BY numeroLit
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne uniquement les lits disponibles.
 */
function getAvailableLits(int $idService): array
{
    $sql = "
        SELECT idLit, numeroLit
        FROM " . T_LIT . "
        WHERE idService = ?
          AND etatLit = 'disponible'
        ORDER BY numeroLit
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne les lits disponibles d'un hôpital.
 */
function getAvailableLitsByHopital(int $idHopital): array
{
    $sql = "
        SELECT l.idLit, l.numeroLit
        FROM " . T_LIT . " l
        JOIN " . T_SERVICE . " s ON s.idService = l.idService
        WHERE s.idHopital = ?
          AND l.etatLit = 'disponible'
        ORDER BY l.numeroLit
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idHopital]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Réserve un lit pour un dossier.
 */
function reserveLitForDossier(
    int $idLit,
    int $idDossier,
    ?int $idInfirmier,
    string $dateDebut,
    string $dateFin
): void {
    $pdo = db();
    $pdo->beginTransaction();

    try {

        $sqlCheckLit = "SELECT etatLit FROM " . T_LIT . " WHERE idLit = ? LIMIT 1 FOR UPDATE";
        $check = $pdo->prepare($sqlCheckLit);
        $check->execute([$idLit]);

        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("Lit introuvable.");
        }
        if (($row['etatLit'] ?? '') !== 'disponible') {
            throw new Exception("Ce lit n'est pas disponible.");
        }

        $sqlCheckDossier = "SELECT 1 FROM " . T_GESTION_LIT . " WHERE idDossier = ? LIMIT 1";
        $checkDossier = $pdo->prepare($sqlCheckDossier);
        $checkDossier->execute([$idDossier]);

        if ($checkDossier->fetchColumn() !== false) {
            throw new Exception("Ce dossier a déjà un lit réservé.");
        }

        $sqlInsert = "
            INSERT INTO " . T_RESERVATION_LIT . " (idLit, idInfirmier, dateDebutReservation, dateFinReservation)
            VALUES (?, ?, ?, ?)
        ";
        $ins = $pdo->prepare($sqlInsert);
        $ins->execute([$idLit, $idInfirmier, $dateDebut, $dateFin]);

        $sqlUpdateLit = "UPDATE " . T_LIT . " SET etatLit = 'reserve' WHERE idLit = ?";
        $upd = $pdo->prepare($sqlUpdateLit);
        $upd->execute([$idLit]);

        $sqlLink = "INSERT INTO " . T_GESTION_LIT . " (idDossier, idLit) VALUES (?, ?)";
        $link = $pdo->prepare($sqlLink);
        $link->execute([$idDossier, $idLit]);

        $pdo->commit();

    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Statistiques globales des lits.
 */
function lits_get_stats(): array
{
    $sql = "
        SELECT
            SUM(CASE WHEN etatLit = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS occupes
        FROM " . T_LIT . "
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'disponibles' => (int)($row['disponibles'] ?? 0),
        'occupes'     => (int)($row['occupes'] ?? 0),
    ];
}
