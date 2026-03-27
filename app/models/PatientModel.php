<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PATIENT MODEL (PDO / MySQL)
|--------------------------------------------------------------------------
| Rôle :
| - Contenir uniquement les requêtes SQL liées aux patients
| - Recevoir les données envoyées par le contrôleur
| - Utiliser PDO et les requêtes préparées
| - Respecter les noms réels des tables en lowercase
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * OUTIL COMMUN
 * ========================================================================= */

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

/* =========================================================================
 * CRÉATION DU PATIENT
 * ========================================================================= */

/**
 * Crée un patient et retourne son identifiant.
 *
 * Remarques :
 * - dateNaissance est obligatoire au format YYYY-MM-DD
 * - les champs optionnels vides sont enregistrés à NULL
 */
function createPatient(array $data): int
{
    $sql = "
        INSERT INTO patient
        (
            nom,
            prenom,
            dateNaissance,
            adresse,
            telephone,
            email,
            genre,
            numeroCarteVitale,
            mutuelle
        )
        VALUES
        (
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
        ':nom'               => trim((string) ($data['nom'] ?? '')),
        ':prenom'            => trim((string) ($data['prenom'] ?? '')),
        ':dateNaissance'     => trim((string) ($data['dateNaissance'] ?? '')),
        ':adresse'           => patientToNull($data['adresse'] ?? null),
        ':telephone'         => patientToNull($data['telephone'] ?? null),
        ':email'             => patientToNull($data['email'] ?? null),
        ':genre'             => trim((string) ($data['genre'] ?? 'Homme')),
        ':numeroCarteVitale' => patientToNull($data['numeroCarteVitale'] ?? null),
        ':mutuelle'          => patientToNull($data['mutuelle'] ?? null),
    ]);

    return (int) db()->lastInsertId();
}

/* =========================================================================
 * MISE À JOUR DU PATIENT
 * ========================================================================= */

/**
 * Met à jour un patient existant.
 *
 * Règles :
 * - idPatient doit être valide
 * - nom, prénom et dateNaissance sont obligatoires
 * - si genre est vide, la valeur "Homme" est utilisée
 * - les champs optionnels vides sont convertis en NULL
 */
function updatePatient(
    mixed $idPatient,
    mixed $nom,
    mixed $prenom,
    mixed $dateNaissance,
    mixed $adresse,
    mixed $telephone,
    mixed $email,
    mixed $genre,
    mixed $numeroCarteVitale,
    mixed $mutuelle
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
        ':nom'               => $nom,
        ':prenom'            => $prenom,
        ':dateNaissance'     => $dateNaissance,
        ':adresse'           => patientToNull($adresse),
        ':telephone'         => patientToNull($telephone),
        ':email'             => patientToNull($email),
        ':genre'             => $genre,
        ':numeroCarteVitale' => patientToNull($numeroCarteVitale),
        ':mutuelle'          => patientToNull($mutuelle),
        ':idPatient'         => $idPatient,
    ]);
}
