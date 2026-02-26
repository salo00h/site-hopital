<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function createPatient(array $data): int
{
    $sql = "INSERT INTO patient
        (nom, prenom, dateNaissance, adresse, telephone, email, genre, numeroCarteVitale, mutuelle)
        VALUES
        (:nom, :prenom, :dateNaissance, :adresse, :telephone, :email, :genre, :numeroCarteVitale, :mutuelle)";

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':nom' => $data['nom'],
        ':prenom' => $data['prenom'],
        ':dateNaissance' => $data['dateNaissance'], // YYYY-MM-DD obligatoire
        ':adresse' => $data['adresse'] ?: null,
        ':telephone' => $data['telephone'] ?: null,
        ':email' => $data['email'] ?: null,
        ':genre' => $data['genre'], // Homme / Femme / Autre
        ':numeroCarteVitale' => $data['numeroCarteVitale'] ?: null,
        ':mutuelle' => $data['mutuelle'] ?: null,
    ]);

    return (int) db()->lastInsertId();
}

function updatePatient($idPatient, $nom, $prenom, $dateNaissance, $adresse, $telephone, $email, $genre, $numeroCarteVitale, $mutuelle)
{
    // حماية أساسية
    $idPatient = (int)$idPatient;
    if ($idPatient <= 0) {
        throw new Exception("ID Patient invalide");
    }

    if ($nom === "" || $prenom === "" || $dateNaissance === "") {
        throw new Exception("Nom, prénom et date de naissance obligatoires");
    }

    // Default genre إذا جا فارغ
    if ($genre === "") {
        $genre = "Homme";
    }

    $sql = "UPDATE patient SET
                nom = :nom,
                prenom = :prenom,
                dateNaissance = :dateNaissance,
                adresse = :adresse,
                telephone = :telephone,
                email = :email,
                genre = :genre,
                numeroCarteVitale = :numeroCarteVitale,
                mutuelle = :mutuelle
            WHERE idPatient = :idPatient";

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
        ':idPatient' => $idPatient
    ]);
}
