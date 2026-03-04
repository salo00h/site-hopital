<?php
declare(strict_types=1);

/*
  ==============================
  PATIENT MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Ici on met le SQL uniquement.
  - Le contrôleur envoie les données à ces fonctions.
  - On utilise PDO + requêtes préparées.
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_tables.php';

/**
 * Crée un patient et renvoie l'idPatient.
 * Remarque :
 * - dateNaissance est obligatoire (format YYYY-MM-DD)
 * - les champs optionnels vides => NULL en base
 */
function createPatient(array $data): int
{
    $sql = "
        INSERT INTO PATIENT
            (nom, prenom, dateNaissance, adresse, telephone, email, genre, numeroCarteVitale, mutuelle)
        VALUES
            (:nom, :prenom, :dateNaissance, :adresse, :telephone, :email, :genre, :numeroCarteVitale, :mutuelle)
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':nom' => $data['nom'],
        ':prenom' => $data['prenom'],
        ':dateNaissance' => $data['dateNaissance'], // obligatoire
        ':adresse' => ($data['adresse'] ?? '') !== '' ? $data['adresse'] : null,
        ':telephone' => ($data['telephone'] ?? '') !== '' ? $data['telephone'] : null,
        ':email' => ($data['email'] ?? '') !== '' ? $data['email'] : null,
        ':genre' => $data['genre'], // Homme / Femme / Autre
        ':numeroCarteVitale' => ($data['numeroCarteVitale'] ?? '') !== '' ? $data['numeroCarteVitale'] : null,
        ':mutuelle' => ($data['mutuelle'] ?? '') !== '' ? $data['mutuelle'] : null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Met à jour un patient.
 * Règles simples  :
 * - idPatient doit être valide
 * - nom / prenom / dateNaissance obligatoires
 * - si genre vide => "Homme"
 * - champs optionnels vides => NULL
 */
function updatePatient(
    $idPatient,
    $nom,
    $prenom,
    $dateNaissance,
    $adresse,
    $telephone,
    $email,
    $genre,
    $numeroCarteVitale,
    $mutuelle
) {
    // 1) Vérifications de base
    $idPatient = (int)$idPatient;
    if ($idPatient <= 0) {
        throw new Exception("ID Patient invalide");
    }

    if ($nom === "" || $prenom === "" || $dateNaissance === "") {
        throw new Exception("Nom, prénom et date de naissance obligatoires");
    }

    // Valeur par défaut si genre vide
    if ($genre === "") {
        $genre = "Homme";
    }

    // 2) Requête UPDATE
    $sql = "
        UPDATE PATIENT SET
            nom = :nom,
            prenom = :prenom,
            dateNaissance = :dateNaissance,
            adresse = :adresse,
            telephone = :telephone,
            email = :email,
            genre = :genre,
            numeroCarteVitale = :numeroCarteVitale,
            mutuelle = :mutuelle
        WHERE idPatient = :idPatient
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':nom' => trim($nom),
        ':prenom' => trim($prenom),
        ':dateNaissance' => $dateNaissance,
        ':adresse' => ($adresse !== "") ? trim($adresse) : null,
        ':telephone' => ($telephone !== "") ? trim($telephone) : null,
        ':email' => ($email !== "") ? trim($email) : null,
        ':genre' => $genre,
        ':numeroCarteVitale' => ($numeroCarteVitale !== "") ? trim($numeroCarteVitale) : null,
        ':mutuelle' => ($mutuelle !== "") ? trim($mutuelle) : null,
        ':idPatient' => $idPatient,
    ]);
}
