<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LIT MODEL (PDO / MySQL)
|--------------------------------------------------------------------------
| Rôle :
| - Gérer uniquement l'accès aux données des lits
| - Utiliser PDO et des requêtes préparées
| - Ne contenir ni logique métier complexe ni affichage
| - Respecter les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * OUTILS COMMUNS
 * ========================================================================= */

/**
 * Retourne une valeur entière d'un champ SQL ou null si aucune ligne n'est trouvée.
 *
 * Comportement :
 * - si $field est vide, la première colonne du résultat est utilisée
 * - sinon, la colonne nommée dans $field est utilisée
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

/* =========================================================================
 * RÉCUPÉRATION DES IDENTIFIANTS LIÉS AU PERSONNEL
 * ========================================================================= */

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
 * Retourne l'idInfirmier associé à un personnel.
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
 * Retourne l'idTechnicien associé à un personnel.
 */
function getTechnicienIdByPersonnel(int $idPersonnel): ?int
{
    return fetchIntOrNull(
        "SELECT idTechnicien FROM technicien WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idTechnicien'
    );
}

/**
 * Retourne l'id d'un service à partir de son nom.
 */
function getServiceIdByName(string $nomService): ?int
{
    return fetchIntOrNull(
        "SELECT idService FROM service WHERE nom = ? LIMIT 1",
        [$nomService],
        'idService'
    );
}

/* =========================================================================
 * STATISTIQUES ET LISTES PAR SERVICE
 * ========================================================================= */

/**
 * Retourne les statistiques des lits par état pour un service précis.
 *
 * Exemple de résultat :
 * [
 *   ['etatLit' => 'disponible', 'nb' => 5],
 *   ['etatLit' => 'reserve', 'nb' => 2]
 * ]
 */
function getLitStatsByService(int $idService): array
{
    $sql = "
        SELECT
            etatLit,
            COUNT(*) AS nb
        FROM lit
        WHERE idService = ?
        GROUP BY etatLit
        ORDER BY etatLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne tous les lits d’un service avec le nom du service.
 */
function getLitsByService(int $idService): array
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
        WHERE l.idService = ?
        ORDER BY l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne uniquement les lits disponibles d’un service donné.
 */
function getAvailableLits(int $idService): array
{
    $sql = "
        SELECT
            idLit,
            numeroLit
        FROM lit
        WHERE idService = ?
          AND etatLit = 'disponible'
        ORDER BY numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne les lits disponibles d’un hôpital, tous services confondus.
 *
 * Le filtrage se fait via la table service grâce à idHopital.
 */
function getAvailableLitsByHopital(int $idHopital): array
{
    $sql = "
        SELECT
            l.idLit,
            l.numeroLit,
            s.nom AS serviceNom
        FROM lit l
        INNER JOIN service s ON s.idService = l.idService
        WHERE s.idHopital = ?
          AND l.etatLit = 'disponible'
        ORDER BY s.nom ASC, l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idHopital]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================================================================
 * RÉSERVATION ET AFFECTATION DES LITS
 * ========================================================================= */

/**
 * Réserve un lit pour un dossier dans une transaction.
 *
 * Étapes :
 * 1) vérifier que le lit existe et est disponible
 * 2) vérifier que le dossier n'a pas déjà un lit
 * 3) enregistrer la réservation
 * 4) passer le lit à l'état "reserve"
 * 5) lier le lit au dossier dans gestion_lit
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
        // 1) Vérifier le lit et verrouiller la ligne
        $sqlCheckLit = "
            SELECT etatLit
            FROM lit
            WHERE idLit = ?
            LIMIT 1
            FOR UPDATE
        ";
        $check = $pdo->prepare($sqlCheckLit);
        $check->execute([$idLit]);

        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Lit introuvable.');
        }

        if (($row['etatLit'] ?? '') !== 'disponible') {
            throw new Exception(\"Ce lit n'est pas disponible.\");
        }

        // 2) Vérifier que le dossier n'a pas déjà un lit
        $sqlCheckDossier = "
            SELECT 1
            FROM gestion_lit
            WHERE idDossier = ?
            LIMIT 1
        ";
        $checkDossier = $pdo->prepare($sqlCheckDossier);
        $checkDossier->execute([$idDossier]);

        if ($checkDossier->fetchColumn() !== false) {
            throw new Exception('Ce dossier a déjà un lit réservé.');
        }

        // 3) Enregistrer la réservation
        $sqlInsert = "
            INSERT INTO reservation_lit (
                idLit,
                idInfirmier,
                dateDebutReservation,
                dateFinReservation
            )
            VALUES (?, ?, ?, ?)
        ";
        $ins = $pdo->prepare($sqlInsert);
        $ins->execute([$idLit, $idInfirmier, $dateDebut, $dateFin]);

        // 4) Mettre le lit à l'état réservé
        $sqlUpdateLit = "
            UPDATE lit
            SET etatLit = 'reserve'
            WHERE idLit = ?
        ";
        $upd = $pdo->prepare($sqlUpdateLit);
        $upd->execute([$idLit]);

        // 5) Lier le lit au dossier
        $sqlLink = "
            INSERT INTO gestion_lit (
                idDossier,
                idLit,
                idInfirmierAttribution,
                dateAttribution
            )
            VALUES (?, ?, ?, NOW())
        ";
        $link = $pdo->prepare($sqlLink);
        $link->execute([$idDossier, $idLit, $idInfirmier]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/* =========================================================================
 * STATISTIQUES GÉNÉRALES DES LITS
 * ========================================================================= */

/**
 * Retourne des statistiques sur les lits d’un service.
 *
 * Pour le médecin :
 * - disponibles = lits disponibles du service
 * - occupes = lits occupés ou réservés du service
 */
function lits_get_stats(int $idService): array
{
    $sql = "
        SELECT
            SUM(CASE WHEN etatLit = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS occupes
        FROM lit
        WHERE idService = ?
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'disponibles' => (int) ($row['disponibles'] ?? 0),
        'occupes'     => (int) ($row['occupes'] ?? 0),
    ];
}

/**
 * Compte le nombre de lits disponibles dans un service donné.
 */
function lits_count_disponibles_by_service(int $idService): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = 'disponible'
          AND idService = ?
    ";

    return (int) (fetchIntOrNull($sql, [$idService], 'nb') ?? 0);
}

/**
 * Compte le nombre de lits réservés dans un service donné.
 */
function lits_count_reserves_by_service(int $idService): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = 'reserve'
          AND idService = ?
    ";

    return (int) (fetchIntOrNull($sql, [$idService], 'nb') ?? 0);
}

/**
 * Compte le nombre de lits occupés ou réservés dans un service donné.
 */
function lits_count_occupes_et_reserves_by_service(int $idService): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit IN ('occupe', 'reserve')
          AND idService = ?
    ";

    return (int) (fetchIntOrNull($sql, [$idService], 'nb') ?? 0);
}

/**
 * Retourne le taux d’occupation des lits pour un service donné.
 *
 * Le taux prend en compte les lits occupés et réservés.
 */
function lits_get_taux_occupation_by_service(int $idService): int
{
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN etatLit IN ('occupe', 'reserve') THEN 1 ELSE 0 END) AS nbOcc
        FROM lit
        WHERE idService = ?
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idService]);

    $row   = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($row['total'] ?? 0);
    $nbOcc = (int) ($row['nbOcc'] ?? 0);

    if ($total <= 0) {
        return 0;
    }

    return (int) round(($nbOcc / $total) * 100);
}

/**
 * Compte le nombre de lits selon leur état.
 */
function lits_count_by_etat(string $etat): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = ?
    ";

    return (int) (fetchIntOrNull($sql, [$etat], 'nb') ?? 0);
}

/* =========================================================================
 * COMPATIBILITÉ AVEC ANCIENS CONTROLLERS / DASHBOARDS
 * ========================================================================= */

/**
 * Nombre global de lits disponibles.
 */
function lits_count_disponibles(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = 'disponible'
    ";

    return (int) (fetchIntOrNull($sql, [], 'nb') ?? 0);
}

/**
 * Nombre global de lits réservés.
 */
function lits_count_reserves(): int
{
    $sql = "
        SELECT COUNT(*) AS nb
        FROM lit
        WHERE etatLit = 'reserve'
    ";

    return (int) (fetchIntOrNull($sql, [], 'nb') ?? 0);
}

/**
 * Nombre global de lits occupés ou réservés.
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
 * Retourne le taux d’occupation global des lits.
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

    $row   = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($row['total'] ?? 0);
    $nbOcc = (int) ($row['nbOcc'] ?? 0);

    if ($total <= 0) {
        return 0;
    }

    return (int) round(($nbOcc / $total) * 100);
}

/* =========================================================================
 * LISTES ET DÉTAILS GÉNÉRAUX
 * ========================================================================= */

/**
 * Retourne la liste globale de tous les lits.
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
        WHERE idLit = ?
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idLit]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Met à jour l’état d’un lit.
 */
function lit_update_etat(int $idLit, string $etatLit): bool
{
    $sql = "
        UPDATE lit
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

/* =========================================================================
 * PARTIE TECHNICIEN
 * ========================================================================= */

/**
 * Récupère tous les lits avec leur service,
 * triés par priorité d’état.
 */
function lits_get_all_for_technicien(): array
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
        ORDER BY
            CASE l.etatLit
                WHEN 'en_panne' THEN 1
                WHEN 'maintenance' THEN 2
                WHEN 'HS' THEN 3
                WHEN 'reserve' THEN 4
                WHEN 'occupe' THEN 5
                WHEN 'disponible' THEN 6
                ELSE 99
            END,
            s.nom ASC,
            l.numeroLit ASC
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Récupère le détail d’un lit spécifique pour le technicien.
 */
function lit_get_detail_for_technicien(int $idLit): ?array
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
        WHERE l.idLit = ?
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idLit]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Retourne la dernière opération de maintenance d’un lit.
 */
function lit_get_last_maintenance(int $idLit): ?array
{
    $sql = "
        SELECT
            idMaintenanceLit,
            idLit,
            idTechnicien,
            dateDebutLit,
            dateFinLit,
            problemeLit
        FROM maintenance_lit
        WHERE idLit = ?
        ORDER BY idMaintenanceLit DESC
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([$idLit]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Ouvre une nouvelle maintenance pour un lit.
 */
function maintenance_lit_open(int $idLit, int $idTechnicien, string $probleme): bool
{
    $sql = "
        INSERT INTO maintenance_lit (
            idLit,
            idTechnicien,
            dateDebutLit,
            dateFinLit,
            problemeLit
        ) VALUES (
            :idLit,
            :idTechnicien,
            CURDATE(),
            NULL,
            :probleme
        )
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':idLit'        => $idLit,
        ':idTechnicien' => $idTechnicien,
        ':probleme'     => $probleme,
    ]);
}

/**
 * Ferme la dernière maintenance ouverte d’un lit.
 *
 * La ligne concernée est celle dont la date de fin est encore NULL.
 */
function maintenance_lit_close_open(int $idLit): bool
{
    $sql = "
        UPDATE maintenance_lit
        SET dateFinLit = CURDATE()
        WHERE idLit = :idLit
          AND dateFinLit IS NULL
        ORDER BY idMaintenanceLit DESC
        LIMIT 1
    ";

    $stmt = db()->prepare($sql);

    return $stmt->execute([
        ':idLit' => $idLit,
    ]);
}
