<?php
declare(strict_types=1);

// ===============================
// EXAMEN MODEL
// ===============================
// Rôle : gérer les demandes d'examen (INSERT)

require_once APP_PATH . '/config/database.php';

function examen_create(
    int $idDossier,
    string $typeExamen,
    string $noteMedecin
): bool
{
    $pdo = db();

    $sql = "
        INSERT INTO EXAMEN
        (idDossier, typeExamen, noteMedecin, dateDemande, statut)
        VALUES
        (:idDossier, :typeExamen, :noteMedecin, NOW(), 'EN_ATTENTE')
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idDossier'   => $idDossier,
        ':typeExamen'  => $typeExamen,
        ':noteMedecin' => $noteMedecin
    ]);
}