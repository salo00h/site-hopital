<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PatientModel.php';

function getAllDossiers(string $q = ''): array
{
    $sql = "SELECT
                d.idDossier,
                d.dateAdmission,
                d.statut,
                d.niveau,
                d.delaiPriseCharge,
                l.idLit,
                l.numeroLit,
                p.idPatient,
                p.nom,
                p.prenom,
                p.dateNaissance,
                p.genre
            FROM DOSSIER_PATIENT d
            INNER JOIN PATIENT p ON p.idPatient = d.idPatient
            LEFT JOIN GESTION_LIT gl ON gl.idDossier = d.idDossier
            LEFT JOIN LIT l ON l.idLit = gl.idLit
            WHERE 1=1";

    $params = [];

    if ($q !== '') {
        $sql .= " AND (p.nom LIKE :q OR p.prenom LIKE :q OR d.idDossier = :idq)";
        $params[':q'] = '%' . $q . '%';
        $params[':idq'] = ctype_digit($q) ? (int)$q : 0;
    }

    $sql .= " ORDER BY d.idDossier DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function getDossierById(int $idDossier): ?array
{
    $sql = "SELECT
                d.*,
                l.idLit,
                l.numeroLit,
                l.etatLit,
                p.nom, p.prenom, p.dateNaissance, p.adresse, p.telephone, p.email, p.genre, p.numeroCarteVitale, p.mutuelle
            FROM DOSSIER_PATIENT d
            INNER JOIN PATIENT p ON p.idPatient = d.idPatient
            LEFT JOIN GESTION_LIT gl ON gl.idDossier = d.idDossier
            LEFT JOIN LIT l ON l.idLit = gl.idLit
            WHERE d.idDossier = :id
            LIMIT 1";

    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retourne le lit lié au dossier (ou null si aucun).
 * Utilisé pour empêcher une double réservation et pour afficher le numéro du lit.
 */
function getLitForDossier(int $idDossier): ?array
{
    $stmt = db()->prepare(
        "SELECT l.idLit, l.numeroLit, l.etatLit
         FROM GESTION_LIT gl
         INNER JOIN LIT l ON l.idLit = gl.idLit
         WHERE gl.idDossier = :id
         LIMIT 1"
    );
    $stmt->execute([':id' => $idDossier]);

    $row = $stmt->fetch();
    return $row ?: null;
}

function createDossier(int $idPatient, array $data): int
{
    $sql = "INSERT INTO DOSSIER_PATIENT
        (idPatient, idHopital, dateCreation, dateAdmission, dateSortie,
         historiqueMedical, antecedant, etat_entree, diagnostic, examen, traitements,
         statut, niveau, delaiPriseCharge, idTransfert)
        VALUES
        (:idPatient, :idHopital, :dateCreation, :dateAdmission, :dateSortie,
         :historiqueMedical, :antecedant, :etat_entree, :diagnostic, :examen, :traitements,
         :statut, :niveau, :delaiPriseCharge, :idTransfert)";

    $stmt = db()->prepare($sql);
    $stmt->execute([
     ':idPatient' => $idPatient,
     ':idHopital' => $data['idHopital'],
     ':dateCreation' => date('Y-m-d'),
     ':dateAdmission' => ($data['dateAdmission'] ?? '') ?: null,
     ':dateSortie' => $data['dateSortie'] ?? null,

     ':historiqueMedical' => ($data['historiqueMedical'] ?? '') ?: null,
     ':antecedant' => ($data['antecedant'] ?? '') ?: null,
     ':etat_entree' => ($data['etat_entree'] ?? '') ?: null,
     ':diagnostic' => ($data['diagnostic'] ?? '') ?: null,
     ':examen' => ($data['examen'] ?? '') ?: null,
     ':traitements' => ($data['traitements'] ?? '') ?: null,

     ':statut' => $data['statut'],
     ':niveau' => $data['niveau'],
     ':delaiPriseCharge' => $data['delaiPriseCharge'],
     ':idTransfert' => ($data['idTransfert'] ?? 0) ?: null,
    ]);

    return (int) db()->lastInsertId();
}

function createPatientAndDossier(array $patient, array $dossier): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $idPatient = createPatient($patient);
        $idDossier = createDossier($idPatient, $dossier);

        $pdo->commit();
        return $idDossier;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateDossier($idDossier, $dateAdmission, $dateSortie, $historiqueMedical, $antecedant, $etat_entree, $diagnostic, $examen, $traitements, $statut, $niveau, $delai)
{
    $sql = "UPDATE DOSSIER_PATIENT SET
                dateAdmission = :dateAdmission,
                dateSortie = :dateSortie,
                historiqueMedical = :historiqueMedical,
                antecedant = :antecedant,
                etat_entree = :etat_entree,
                diagnostic = :diagnostic,
                examen = :examen,
                traitements = :traitements,
                statut = :statut,
                niveau = :niveau,
                delaiPriseCharge = :delai
            WHERE idDossier = :idDossier";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':dateAdmission' => ($dateAdmission !== "") ? $dateAdmission : null,
        ':dateSortie' => ($dateSortie !== "") ? $dateSortie : null,
        ':historiqueMedical' => ($historiqueMedical !== "") ? $historiqueMedical : null,
        ':antecedant' => ($antecedant !== "") ? $antecedant : null,
        ':etat_entree' => ($etat_entree !== "") ? $etat_entree : null,
        ':diagnostic' => ($diagnostic !== "") ? $diagnostic : null,
        ':examen' => ($examen !== "") ? $examen : null,
        ':traitements' => ($traitements !== "") ? $traitements : null,
        ':statut' => $statut,
        ':niveau' => $niveau,
        ':delai' => $delai,
        ':idDossier' => (int)$idDossier
    ]);
}