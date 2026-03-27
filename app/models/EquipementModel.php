<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MODEL : EquipementModel
|--------------------------------------------------------------------------
| Rôle :
| - Contenir uniquement les requêtes SQL liées aux équipements
| - Ne pas inclure de logique métier ni d'affichage
| - Fournir des fonctions appelées par les contrôleurs
| - Utiliser les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/config/database.php';

/* =========================================================================
 * STATISTIQUES GÉNÉRALES
 * ========================================================================= */

/**
 * Retourne les statistiques des équipements.
 *
 * Résultat :
 * - nombre d'équipements disponibles par type
 * - nombre total d'équipements par type
 *
 * Utilisation :
 * - dashboard médecin
 */
function equipements_get_stats(): array
{
    $pdo = db();

    $sql = "
        SELECT
            typeEquipement AS type,
            SUM(CASE WHEN etatEquipement = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            COUNT(*) AS total
        FROM equipement
        GROUP BY typeEquipement
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Compte le nombre total d'équipements disponibles.
 */
function equipements_count_disponibles(): int
{
    $pdo = db();

    $sql = "
        SELECT COUNT(*)
        FROM equipement
        WHERE etatEquipement = 'disponible'
    ";

    return (int) $pdo->query($sql)->fetchColumn();
}

/**
 * Compte le nombre d’équipements selon un état donné.
 */
function equipements_count_by_etat(string $etat): int
{
    $pdo = db();

    $sql = "
        SELECT COUNT(*) AS nb
        FROM equipement
        WHERE etatEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$etat]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return (int) ($row['nb'] ?? 0);
}

/* =========================================================================
 * LISTES ET LECTURE DES ÉQUIPEMENTS
 * ========================================================================= */

/**
 * Retourne tous les équipements, tous états confondus.
 *
 * Utilisation :
 * - affichage global dans la liste médecin
 */
function equipements_get_all(): array
{
    $pdo = db();

    $sql = "
        SELECT
            idEquipement,
            typeEquipement,
            numeroEquipement,
            localisation,
            etatEquipement
        FROM equipement
        ORDER BY typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un équipement par son identifiant.
 *
 * Utilisation :
 * - vérifier l’état avant réservation ou action
 */
function equipement_get_by_id(int $idEquipement): ?array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM equipement
        WHERE idEquipement = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);

    $equipement = $stmt->fetch(PDO::FETCH_ASSOC);

    return $equipement ?: null;
}

/**
 * Retourne la liste des équipements avec le patient associé si présent.
 *
 * Permet d'afficher :
 * - l'équipement
 * - le dossier éventuel
 * - le nom du patient lié
 */
function equipements_get_all_with_patient(): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement,
            d.idDossier,
            p.nom,
            p.prenom
        FROM equipement e
        LEFT JOIN gestion_equipement g
            ON g.idEquipement = e.idEquipement
        LEFT JOIN dossier_patient d
            ON d.idDossier = g.idDossier
        LEFT JOIN patient p
            ON p.idPatient = d.idPatient
        ORDER BY
            CASE e.etatEquipement
                WHEN 'reserve' THEN 1
                WHEN 'occupe' THEN 2
                WHEN 'disponible' THEN 3
                WHEN 'en_panne' THEN 4
                WHEN 'maintenance' THEN 5
                WHEN 'HS' THEN 6
                ELSE 99
            END,
            e.typeEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* =========================================================================
 * MISE À JOUR DE L’ÉTAT DES ÉQUIPEMENTS
 * ========================================================================= */

/**
 * Passe un équipement à l’état "occupe".
 *
 * Utilisation :
 * - après validation d'une réservation ou d'une affectation
 */
function equipement_set_occupe(int $idEquipement): void
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = 'occupe'
        WHERE idEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);
}

/**
 * Passe un équipement à l’état "reserve".
 */
function equipement_set_reserve(int $idEquipement): void
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = 'reserve'
        WHERE idEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);
}

/**
 * Passe un équipement à l’état "en_panne".
 */
function equipement_set_panne(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = 'en_panne'
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':id' => $idEquipement,
    ]);
}

/**
 * Met à jour l’état d’un équipement avec une valeur fournie.
 */
function equipement_update_etat(int $idEquipement, string $etat): bool
{
    $pdo = db();

    $sql = "
        UPDATE equipement
        SET etatEquipement = :etat
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':etat' => $etat,
        ':id'   => $idEquipement,
    ]);
}

/**
 * Vérifie si un équipement est réservable.
 *
 * Règle :
 * - seul l’état "disponible" autorise la réservation
 */
function equipement_is_reservable(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        SELECT etatEquipement
        FROM equipement
        WHERE idEquipement = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $idEquipement,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $etat = (string) ($row['etatEquipement'] ?? '');

    return $etat === 'disponible';
}

/* =========================================================================
 * LIAISONS DOSSIER / ÉQUIPEMENT
 * ========================================================================= */

/**
 * Ajoute une liaison entre un dossier et un équipement.
 *
 * Cette fonction enregistre la réservation dans gestion_equipement.
 *
 * Important :
 * - retourne false si l'équipement n'est pas réservable
 * - retourne true si le lien existe déjà
 * - ne gère aucun affichage ni redirection
 */
function gestion_equipement_add(
    int $idDossier,
    int $idEquipement,
    ?int $idPersonnelAction = null,
    ?string $roleAction = null
): bool {
    $pdo = db();

    if (!equipement_is_reservable($idEquipement)) {
        return false;
    }

    $checkSql = "
        SELECT COUNT(*) AS nb
        FROM gestion_equipement
        WHERE idDossier = ? AND idEquipement = ?
    ";

    $check = $pdo->prepare($checkSql);
    $check->execute([$idDossier, $idEquipement]);

    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ((int) ($row['nb'] ?? 0) > 0) {
        return true;
    }

    $sql = "
        INSERT INTO gestion_equipement
            (idDossier, idEquipement, idPersonnelAction, roleAction, dateAction)
        VALUES
            (?, ?, ?, ?, NOW())
    ";

    $stmt = $pdo->prepare($sql);

    return (bool) $stmt->execute([
        $idDossier,
        $idEquipement,
        $idPersonnelAction,
        $roleAction,
    ]);
}

/**
 * Retourne les équipements liés à un dossier.
 *
 * Utilisation :
 * - affichage des équipements affectés dans la vue médecin
 */
function gestion_equipements_by_dossier(int $idDossier): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement
        FROM gestion_equipement g
        JOIN equipement e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier = ?
        ORDER BY e.typeEquipement ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idDossier]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne un résumé texte des équipements par dossier.
 *
 * Exemple :
 * [10 => "Cardiologie#2, Radiologie#1"]
 */
function equipements_resume_par_dossier(array $idsDossiers): array
{
    if (empty($idsDossiers)) {
        return [];
    }

    $idsDossiers = array_values(array_unique(array_map('intval', $idsDossiers)));
    $idsDossiers = array_values(array_filter(
        $idsDossiers,
        static fn(int $id): bool => $id > 0
    ));

    if (empty($idsDossiers)) {
        return [];
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($idsDossiers), '?'));

    $sql = "
        SELECT
            g.idDossier,
            GROUP_CONCAT(
                CONCAT(e.typeEquipement, '#', e.numeroEquipement)
                ORDER BY e.typeEquipement
                SEPARATOR ', '
            ) AS resume
        FROM gestion_equipement g
        JOIN equipement e ON e.idEquipement = g.idEquipement
        WHERE g.idDossier IN ($placeholders)
        GROUP BY g.idDossier
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($idsDossiers);

    $out = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(int) $row['idDossier']] = (string) $row['resume'];
    }

    return $out;
}

/**
 * Supprime toutes les liaisons d’un équipement dans gestion_equipement.
 */
function gestion_equipement_delete_by_equipement(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        DELETE FROM gestion_equipement
        WHERE idEquipement = ?
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([$idEquipement]);
}

/* =========================================================================
 * PARTIE TECHNICIEN
 * ========================================================================= */

/**
 * Retourne tous les équipements pour le technicien,
 * avec le service associé et un tri par priorité d’état.
 */
function equipements_get_all_for_technicien(): array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement,
            e.idService,
            s.nom AS serviceNom
        FROM equipement e
        LEFT JOIN service s ON s.idService = e.idService
        ORDER BY
            CASE e.etatEquipement
                WHEN 'en_panne' THEN 1
                WHEN 'maintenance' THEN 2
                WHEN 'HS' THEN 3
                WHEN 'reserve' THEN 4
                WHEN 'occupe' THEN 5
                WHEN 'disponible' THEN 6
                ELSE 99
            END,
            s.nom ASC,
            e.typeEquipement ASC,
            e.numeroEquipement ASC
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Retourne le détail complet d’un équipement pour le technicien.
 */
function equipement_get_detail_for_technicien(int $idEquipement): ?array
{
    $pdo = db();

    $sql = "
        SELECT
            e.idEquipement,
            e.typeEquipement,
            e.numeroEquipement,
            e.localisation,
            e.etatEquipement,
            e.idService,
            s.nom AS serviceNom
        FROM equipement e
        LEFT JOIN service s ON s.idService = e.idService
        WHERE e.idEquipement = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Retourne la dernière maintenance d’un équipement.
 */
function equipement_get_last_maintenance(int $idEquipement): ?array
{
    $pdo = db();

    $sql = "
        SELECT
            idMaintenanceEquipement,
            idEquipement,
            idTechnicien,
            dateDebutEquipement,
            dateFinEquipement,
            problemeEquipement
        FROM maintenance_equipement
        WHERE idEquipement = ?
        ORDER BY idMaintenanceEquipement DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipement]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Ouvre une nouvelle maintenance pour un équipement.
 */
function maintenance_equipement_open(int $idEquipement, int $idTechnicien, string $probleme): bool
{
    $pdo = db();

    $sql = "
        INSERT INTO maintenance_equipement (
            idEquipement,
            idTechnicien,
            dateDebutEquipement,
            dateFinEquipement,
            problemeEquipement
        ) VALUES (
            :idEquipement,
            :idTechnicien,
            CURDATE(),
            NULL,
            :probleme
        )
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idEquipement' => $idEquipement,
        ':idTechnicien' => $idTechnicien,
        ':probleme'     => $probleme,
    ]);
}

/**
 * Ferme la dernière maintenance ouverte d’un équipement.
 *
 * La maintenance concernée est celle dont la date de fin est NULL.
 */
function maintenance_equipement_close_open(int $idEquipement): bool
{
    $pdo = db();

    $sql = "
        UPDATE maintenance_equipement
        SET dateFinEquipement = CURDATE()
        WHERE idEquipement = :idEquipement
          AND dateFinEquipement IS NULL
        ORDER BY idMaintenanceEquipement DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idEquipement' => $idEquipement,
    ]);
}
