<?php
declare(strict_types=1);

/*
  ==============================
  PATIENT MODEL (PDO / MySQL)
  ==============================
  Rôle :
  - Contenir uniquement les requêtes SQL liées aux patients.
  - Le contrôleur appelle ces fonctions.
  - Utilisation de PDO avec requêtes préparées.
  - Respect des noms réels des tables en lowercase.
*/

require_once __DIR__ . '/../config/database.php';

/**
 * Transforme une valeur vide en NULL.
 */
function patientToNull(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    return $value;
}

/**
 * Crée un patient et retourne son identifiant.
 * La date de naissance est obligatoire.
 */
function createPatient(array $data): int
{
    $sql = "
        INSERT INTO patient (
            nom,
            prenom,
            dateNaissance,
            adresse,
            telephone,
            email,
            genre,
            numeroCarteVitale,
            mutuelle
        ) VALUES (
            :nom,
            :prenom,
            :dateNaissance,
            :adresse,
            :telephone,
            :email,
            :genre,
            :numeroCarteVitale,
            :mutuelle
        )
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':nom' => trim((string)($data['nom'] ?? '')),
        ':prenom' => trim((string)($data['prenom'] ?? '')),
        ':dateNaissance' => trim((string)($data['dateNaissance'] ?? '')),
        ':adresse' => patientToNull($data['adresse'] ?? null),
        ':telephone' => patientToNull($data['telephone'] ?? null),
        ':email' => patientToNull($data['email'] ?? null),
        ':genre' => trim((string)($data['genre'] ?? 'Homme')),
        ':numeroCarteVitale' => patientToNull($data['numeroCarteVitale'] ?? null),
        ':mutuelle' => patientToNull($data['mutuelle'] ?? null),
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Met à jour un patient existant.
 * Les champs optionnels vides sont enregistrés en NULL.
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
): void {
    $idPatient = (int) $idPatient;

    if ($idPatient <= 0) {
        throw new Exception('ID Patient invalide');
    }

    $nom = trim((string) $nom);
    $prenom = trim((string) $prenom);
    $dateNaissance = trim((string) $dateNaissance);
    $genre = trim((string) $genre);

    if ($nom === '' || $prenom === '' || $dateNaissance === '') {
        throw new Exception('Nom, prénom et date de naissance obligatoires');
    }

    if ($genre === '') {
        $genre = 'Homme';
    }

    $sql = "
        UPDATE patient
        SET
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
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':dateNaissance' => $dateNaissance,
        ':adresse' => patientToNull($adresse),
        ':telephone' => patientToNull($telephone),
        ':email' => patientToNull($email),
        ':genre' => $genre,
        ':numeroCarteVitale' => patientToNull($numeroCarteVitale),
        ':mutuelle' => patientToNull($mutuelle),
        ':idPatient' => $idPatient,
    ]);
}
