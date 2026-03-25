<?php
declare(strict_types=1);

/*
  ==============================
  LIT MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Accès aux données uniquement (SQL).
  - PDO + requêtes préparées.
*/

require_once __DIR__ . '/../config/database.php';

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
        // Si on n'a pas précisé de champ, on prend la 1ère colonne
        $value = array_values($row)[0] ?? null;
    } else {
        $value = $row[$field] ?? null;
    }

    return ($value === null) ? null : (int) $value;
}

/**
 * Retourne l'idService d'un personnel (ou null si introuvable).
 */
function getServiceIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idService FROM PERSONNEL WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idService'
    );
}

/**
 * Retourne l'idInfirmier lié à un personnel (ou null si introuvable).
 */
function getInfirmierIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idInfirmier FROM INFIRMIER WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idInfirmier'
    );
}

/**
 * Statistiques des lits par état (disponible / occupé / reserve / etc.)
 * Exemple résultat : [ ['etatLit' => 'disponible', 'nb' => 5], ... ]
 */
function getLitStatsByService(int $idService = 0): array
{
    $sql = "
        SELECT etatLit, COUNT(*) AS nb
        FROM LIT
        GROUP BY etatLit
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne tous les lits avec le nom du service (pour affichage).
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
        FROM LIT l
        LEFT JOIN SERVICE s ON s.idService = l.idService
        ORDER BY s.nom ASC, l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne uniquement les lits disponibles d'un service.
 */
function getAvailableLits(int $idService): array
{
    $sql = "
        SELECT idLit, numeroLit
        FROM LIT
        WHERE idService = ?
          AND etatLit = 'disponible'
        ORDER BY numeroLit
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne les lits disponibles d'un hôpital (tous services confondus).
 * On passe par la table SERVICE pour filtrer par idHopital.
 *
 */
function getAvailableLitsByHopital(int $idHopital): array
{
    $sql = "
        SELECT 
            l.idLit, 
            l.numeroLit,
            s.nom AS serviceNom
        FROM LIT l
        JOIN SERVICE s ON s.idService = l.idService
        WHERE s.idHopital = ?
          AND l.etatLit = 'disponible'
        ORDER BY s.nom ASC, l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idHopital]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
/**
 * Réserve un lit pour un dossier (transaction).
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
        // 1) Vérifier le lit (et verrouiller la ligne pour éviter une double réservation)
        $sqlCheckLit = "SELECT etatLit FROM LIT WHERE idLit = ? LIMIT 1 FOR UPDATE";
        $check = $pdo->prepare($sqlCheckLit);
        $check->execute([$idLit]);

        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("Lit introuvable.");
        }
        if (($row['etatLit'] ?? '') !== 'disponible') {
            throw new Exception("Ce lit n'est pas disponible.");
        }

        // 2) Vérifier que le dossier n'a pas déjà un lit
        $sqlCheckDossier = "SELECT 1 FROM GESTION_LIT WHERE idDossier = ? LIMIT 1";
        $checkDossier = $pdo->prepare($sqlCheckDossier);
        $checkDossier->execute([$idDossier]);

        if ($checkDossier->fetchColumn() !== false) {
            throw new Exception("Ce dossier a déjà un lit réservé.");
        }

        // 3) Insérer la réservation
        $sqlInsert = "
            INSERT INTO RESERVATION_LIT (idLit, idInfirmier, dateDebutReservation, dateFinReservation)
            VALUES (?, ?, ?, ?)
        ";
        $ins = $pdo->prepare($sqlInsert);
        $ins->execute([$idLit, $idInfirmier, $dateDebut, $dateFin]);

        // 4) Mettre à jour l'état du lit
        $sqlUpdateLit = "UPDATE LIT SET etatLit = 'reserve' WHERE idLit = ?";
        $upd = $pdo->prepare($sqlUpdateLit);
        $upd->execute([$idLit]);

        // 5) Lier le lit au dossier
        $sqlLink = "INSERT INTO GESTION_LIT (idDossier, idLit) VALUES (?, ?)";
        $link = $pdo->prepare($sqlLink);
        $link->execute([$idDossier, $idLit]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Retourne des statistiques globales sur les lits.
 * Pour le médecin :
 * - "Occupés" = lits non disponibles = occupe + reserve
 */
function lits_get_stats(): array
{
    $sql = "
        SELECT
            SUM(CASE WHEN etatLit = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS occupes
        FROM LIT
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'disponibles' => (int)($row['disponibles'] ?? 0),
        'occupes'     => (int)($row['occupes'] ?? 0),
    ];
}

/**
 * Nombre de lits disponibles.
 */
function lits_count_disponibles(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM LIT
        WHERE etatLit = 'disponible'
    ";

    return (int)(fetchIntOrNull($sql, [], 'nb') ?? 0);
}

/**
 * Nombre de lits réservés.
 */
function lits_count_reserves(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM LIT
        WHERE etatLit = 'reserve'
    ";

    return (int)(fetchIntOrNull($sql, [], 'nb') ?? 0);
}


/**
 * Nombre de lits occupés + réservés.
 */
function lits_count_occupes_et_reserves(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM LIT
        WHERE etatLit IN ('occupe', 'reserve')
    ";

    return (int)(fetchIntOrNull($sql, [], 'nb') ?? 0);
}

/**
 * Taux d’occupation global.
 */
function lits_get_taux_occupation_global(): int
{
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS nbOcc
        FROM LIT
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int)($row['total'] ?? 0);
    $nbOcc = (int)($row['nbOcc'] ?? 0);

    if ($total <= 0) {
        return 0;
    }

    return (int)round(($nbOcc / $total) * 100);
}

/**
 * Liste globale des lits.
 */
function lits_get_all(): array
{
    $sql = "
        SELECT idLit, numeroLit, etatLit, idService
        FROM LIT
        ORDER BY numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un lit par son ID.
 */
function lit_get_by_id(int $idLit): ?array
{
    $sql = "
        SELECT idLit, numeroLit, etatLit, idService
        FROM LIT
        WHERE idLit = ?
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idLit]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Mise à jour de l’état d’un lit.
 */
function lit_update_etat(int $idLit, string $etatLit): bool
{
    $sql = "
        UPDATE LIT
        SET etatLit = :etat
        WHERE idLit = :id
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':etat' => $etatLit,
        ':id'   => $idLit,
    ]);
}