<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function getServiceIdByPersonnel(int $idPersonnel): ?int
{
    $stmt = db()->prepare("SELECT idService FROM personnel WHERE idPersonnel = ? LIMIT 1");
    $stmt->execute([$idPersonnel]);
    $row = $stmt->fetch();

    return $row ? (int)$row['idService'] : null;
}

function getInfirmierIdByPersonnel(int $idPersonnel): ?int
{
    $stmt = db()->prepare("SELECT idInfirmier FROM infirmier WHERE idPersonnel = ? LIMIT 1");
    $stmt->execute([$idPersonnel]);
    $row = $stmt->fetch();

    return $row ? (int)$row['idInfirmier'] : null;
}

function getLitStatsByService(int $idService): array
{
    $stmt = db()->prepare("
        SELECT etatLit, COUNT(*) AS nb
        FROM lit
        WHERE idService = ?
        GROUP BY etatLit
    ");
    $stmt->execute([$idService]);

    return $stmt->fetchAll() ?: [];
}

function getLitsByService(int $idService): array
{
    $stmt = db()->prepare("
        SELECT idLit, numeroLit, etatLit
        FROM lit
        WHERE idService = ?
        ORDER BY numeroLit
    ");
    $stmt->execute([$idService]);

    return $stmt->fetchAll() ?: [];
}

function getAvailableLits(int $idService): array
{
    $stmt = db()->prepare("
        SELECT idLit, numeroLit
        FROM lit
        WHERE idService = ?
          AND etatLit = 'disponible'
        ORDER BY numeroLit
    ");
    $stmt->execute([$idService]);

    return $stmt->fetchAll() ?: [];
}

function reserveLitForDossier(
    int $idLit,
    int $idDossier,
    int $idInfirmier,
    string $dateDebut,
    string $dateFin
): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Vérifier l'état du lit
        $check = $pdo->prepare("SELECT etatLit FROM lit WHERE idLit = ? LIMIT 1");
        $check->execute([$idLit]);
        $row = $check->fetch();

        if (!$row) {
            throw new Exception("Lit introuvable.");
        }
        if ($row['etatLit'] !== 'disponible') {
            throw new Exception("Ce lit n'est pas disponible.");
        }

        // 1bis) Vérifier que le dossier n'a pas déjà un lit
        $checkDossier = $pdo->prepare("SELECT COUNT(*) AS nb FROM gestion_lit WHERE idDossier = ?");
        $checkDossier->execute([$idDossier]);
        $nb = (int)($checkDossier->fetchColumn() ?: 0);
        if ($nb > 0) {
            throw new Exception("Ce dossier a déjà un lit réservé.");
        }

        // 2) Insérer la réservation
        $ins = $pdo->prepare("
            INSERT INTO reservation_lit (idLit, idInfirmier, dateDebutReservation, dateFinReservation)
            VALUES (?, ?, ?, ?)
        ");
        $ins->execute([$idLit, $idInfirmier, $dateDebut, $dateFin]);

        // 3) Mettre à jour l'état du lit
        $upd = $pdo->prepare("UPDATE lit SET etatLit = 'reserve' WHERE idLit = ?");
        $upd->execute([$idLit]);

        // 4) Lier le lit au dossier
        $link = $pdo->prepare("
            INSERT INTO gestion_lit (idDossier, idLit)
            VALUES (?, ?)
        ");
        $link->execute([$idDossier, $idLit]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
