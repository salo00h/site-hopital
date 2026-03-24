<?php
declare(strict_types=1);

/*
  ==============================
  LIT MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Accès aux données uniquement.
  - Utilisation de PDO avec requêtes préparées.
  - Respect des noms réels des tables en lowercase.
*/

require_once __DIR__ . '/../config/database.php';

/**
 * Retourne une valeur entière d'un champ, ou null si aucune ligne.
 */
function fetchIntOrNull(string $sql, array $params = [], string $field = ''): ?int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $value = ($field === '')
        ? (array_values($row)[0] ?? null)
        : ($row[$field] ?? null);

    return $value === null ? null : (int) $value;
}

/**
 * Retourne l'idService d'un personnel.
 */
function getServiceIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idService FROM personnel WHERE idPersonnel = ? LIMIT 1",
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
        "SELECT idInfirmier FROM infirmier WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idInfirmier'
    );
}

/**
 * Retourne les statistiques des lits par état.
 * Si idService > 0, filtre sur ce service.
 */
/**
 * Retourne les statistiques globales des lits par état.
 * Affiche tous les lits sans filtrer par service.
 */
function getLitStatsByService(int $idService = 0): array
{
    $sql = "
        SELECT etatLit, COUNT(*) AS nb
        FROM lit
        GROUP BY etatLit
        ORDER BY etatLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne tous les lits.
 * Affiche tous les lits sans filtrer par service.
 */
function getLitsByService(int $idService = 0): array
{
    $sql = "
        SELECT
            l.idLit,
            l.numeroLit,
            l.etatLit,
            l.idService,
            s.nom AS serviceNom
        FROM lit l
        LEFT JOIN service s ON s.idService = l.idService
        ORDER BY s.nom ASC, l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
/**
 * Retourne les lits disponibles d'un service.
 */
function getAvailableLits(int $idService): array
{
    $sql = "
        SELECT idLit, numeroLit
        FROM lit
        WHERE idService = :idService
          AND etatLit = :etat
        ORDER BY numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idService' => $idService,
        ':etat' => 'disponible',
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne les lits disponibles d'un hôpital.
 * Filtrage via la table service.
 */
function getAvailableLitsByHopital(int $idHopital): array
{
    $sql = "
        SELECT
            l.idLit,
            l.numeroLit
        FROM lit l
        INNER JOIN service s ON s.idService = l.idService
        WHERE s.idHopital = :idHopital
          AND l.etatLit = :etat
        ORDER BY l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idHopital' => $idHopital,
        ':etat' => 'disponible',
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Réserve un lit pour un dossier dans une transaction.
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
        $sqlCheckLit = "
            SELECT etatLit
            FROM lit
            WHERE idLit = :idLit
            LIMIT 1
            FOR UPDATE
        ";
        $check = $pdo->prepare($sqlCheckLit);
        $check->execute([
            ':idLit' => $idLit,
        ]);

        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Lit introuvable.');
        }

        if (($row['etatLit'] ?? '') !== 'disponible') {
            throw new Exception("Ce lit n'est pas disponible.");
        }

        $sqlCheckDossier = "
            SELECT 1
            FROM gestion_lit
            WHERE idDossier = :idDossier
            LIMIT 1
        ";
        $checkDossier = $pdo->prepare($sqlCheckDossier);
        $checkDossier->execute([
            ':idDossier' => $idDossier,
        ]);

        if ($checkDossier->fetchColumn() !== false) {
            throw new Exception('Ce dossier a déjà un lit réservé.');
        }

        $sqlInsert = "
            INSERT INTO reservation_lit (
                idLit,
                idInfirmier,
                dateDebutReservation,
                dateFinReservation
            ) VALUES (
                :idLit,
                :idInfirmier,
                :dateDebut,
                :dateFin
            )
        ";
        $ins = $pdo->prepare($sqlInsert);
        $ins->execute([
            ':idLit' => $idLit,
            ':idInfirmier' => $idInfirmier,
            ':dateDebut' => $dateDebut,
            ':dateFin' => $dateFin,
        ]);

        $sqlUpdateLit = "
            UPDATE lit
            SET etatLit = :etat
            WHERE idLit = :idLit
        ";
        $upd = $pdo->prepare($sqlUpdateLit);
        $upd->execute([
            ':etat' => 'reserve',
            ':idLit' => $idLit,
        ]);

        $sqlLink = "
            INSERT INTO gestion_lit (idDossier, idLit)
            VALUES (:idDossier, :idLit)
        ";
        $link = $pdo->prepare($sqlLink);
        $link->execute([
            ':idDossier' => $idDossier,
            ':idLit' => $idLit,
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
 * Retourne des statistiques globales sur les lits.
 * Les lits occupés incluent : occupe + reserve.
 */
function lits_get_stats(): array
{
    $sql = "
        SELECT
            SUM(CASE WHEN etatLit = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS occupes
        FROM lit
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'disponibles' => (int) ($row['disponibles'] ?? 0),
        'occupes' => (int) ($row['occupes'] ?? 0),
    ];
}

/**
 * Nombre de lits disponibles.
 */
function lits_count_disponibles(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = :etat
    ";

    return (int) (fetchIntOrNull($sql, [':etat' => 'disponible'], 'nb') ?? 0);
}

/**
 * Nombre de lits réservés.
 */
function lits_count_reserves(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = :etat
    ";

    return (int) (fetchIntOrNull($sql, [':etat' => 'reserve'], 'nb') ?? 0);
}

/**
 * Nombre de lits occupés ou réservés.
 */
function lits_count_occupes_et_reserves(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit IN ('occupe', 'reserve')
    ";

    return (int) (fetchIntOrNull($sql, [], 'nb') ?? 0);
}

/**
 * Taux d'occupation global des lits.
 */
function lits_get_taux_occupation_global(): int
{
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS nbOcc
        FROM lit
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($row['total'] ?? 0);
    $nbOcc = (int) ($row['nbOcc'] ?? 0);

    if ($total <= 0) {
        return 0;
    }

    return (int) round(($nbOcc / $total) * 100);
}

/**
 * Retourne la liste globale des lits.
 */
function lits_get_all(): array
{
    $sql = "
        SELECT
            idLit,
            numeroLit,
            etatLit,
            idService
        FROM lit
        ORDER BY numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un lit par son identifiant.
 */
function lit_get_by_id(int $idLit): ?array
{
    $sql = "
        SELECT
            idLit,
            numeroLit,
            etatLit,
            idService
        FROM lit
        WHERE idLit = :idLit
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':idLit' => $idLit,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Met à jour l'état d'un lit.
 */
function lit_update_etat(int $idLit, string $etatLit): bool
{
    $sql = "
        UPDATE lit
        SET etatLit = :etat
        WHERE idLit = :id
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':etat' => $etatLit,
        ':id' => $idLit,
    ]);
}
