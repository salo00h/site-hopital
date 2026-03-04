<?php
declare(strict_types=1);

/*
  ==============================
  DOSSIER CONTROLLER (MVC)
  ==============================
  - Le contrôleur ne contient pas de SQL.
  - Il récupère les données (GET/POST), vérifie, puis appelle le Model.
  - Ensuite il charge la View.
*/

require_once APP_PATH . '/includes/auth_guard.php';

require_once __DIR__ . '/../models/DossierModel.php';
require_once __DIR__ . '/../models/PatientModel.php';

// Actions médecin
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/TransfertModel.php';

function abort(int $status, string $message): void
{
    http_response_code($status);
    echo $message;
    exit;
}

function requirePost(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        abort(405, "Méthode non autorisée.");
        return false;
    }
    return true;
}

function getIntParam(string $key, int $default = 0): int
{
    return (int)($_REQUEST[$key] ?? $default);
}

function getStrParam(string $key, string $default = ''): string
{
    return trim((string)($_REQUEST[$key] ?? $default));
}

function dossiers_list(): void
{
    $q = getStrParam('q', '');
    $dossiers = getAllDossiers($q);

    // Par défaut : utile si rôle ≠ MEDECIN
    $equipementsResume = [];
    $examensCount = [];
    $transfertsCount = [];

    if (($_SESSION['user']['role'] ?? '') === 'MEDECIN') {
        require_once APP_PATH . '/models/EquipementModel.php';
        require_once APP_PATH . '/models/TransfertModel.php';

        $idsDossiers = array_map(
            static fn($d) => (int)$d['idDossier'],
            $dossiers
        );

        $equipementsResume = equipements_resume_par_dossier($idsDossiers);
        $examensCount = examens_count_by_dossiers($idsDossiers);

        $idsPatients = array_values(array_unique(array_filter(array_map(
            static fn($d) => (int)($d['idPatient'] ?? 0),
            $dossiers
        ))));

        $transfertsCount = transferts_count_by_patients($idsPatients);
    }

    require __DIR__ . '/../views/dossiers/liste.php';
}

/**
 * Détail d'un dossier (infirmier) via ?id=...
 */
function dossier_detail(): void
{
    $id = getIntParam('id', 0);
    if ($id <= 0) {
        abort(400, "ID dossier invalide");
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        abort(404, "Dossier introuvable");
    }

    require __DIR__ . '/../views/dossiers/detail_infirmier.php';
}

/**
 * Formulaire d'édition (GET ?id=...)
 */
function dossier_edit_form(): void
{
    $id = getIntParam('id', 0);
    if ($id <= 0) {
        abort(400, "ID dossier invalide");
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        abort(404, "Dossier introuvable");
    }

    $error = "";
    require __DIR__ . '/../views/dossiers/edit.php';
}

/**
 * Traitement de la mise à jour (POST)
 */
function dossier_update(): void
{
    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);
    $idPatient = getIntParam('idPatient', 0);

    if ($idDossier <= 0 || $idPatient <= 0) {
        abort(400, "IDs invalides");
    }

    // Champs obligatoires patient
    $nom = getStrParam('nom');
    $prenom = getStrParam('prenom');
    $dateNaissance = getStrParam('dateNaissance');

    if ($nom === '' || $prenom === '' || $dateNaissance === '') {
        $dossier = getDossierById($idDossier);
        $error = "Nom / Prénom / Date naissance obligatoires.";
        require __DIR__ . '/../views/dossiers/edit.php';
        return;
    }

    // Données patient (optionnelles)
    $adresse = getStrParam('adresse');
    $telephone = getStrParam('telephone');
    $email = getStrParam('email');
    $genre = getStrParam('genre', 'Homme');
    $numeroCarteVitale = getStrParam('numeroCarteVitale');
    $mutuelle = getStrParam('mutuelle');

    // Données dossier
    $dateAdmission = getStrParam('dateAdmission');
    $dateSortie = getStrParam('dateSortie');
    $historiqueMedical = getStrParam('historiqueMedical');
    $antecedant = getStrParam('antecedant');
    $etat_entree = getStrParam('etat_entree');
    $diagnostic = getStrParam('diagnostic');
    $examen = getStrParam('examen');
    $traitements = getStrParam('traitements');

    $statut = getStrParam('statut', 'ouvert');
    $niveau = getStrParam('niveau', '1');
    $delai = getStrParam('delaiPriseCharge', 'NonImmediat');

    // Mise à jour en base
    updatePatient(
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
    );

    updateDossier(
        $idDossier,
        $dateAdmission,
        $dateSortie,
        $historiqueMedical,
        $antecedant,
        $etat_entree,
        $diagnostic,
        $examen,
        $traitements,
        $statut,
        $niveau,
        $delai
    );

    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}

/**
 * ==============================
 * Formulaire de création (infirmier)
 * ==============================
 */
function dossier_create_form(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    $error = '';
    require __DIR__ . '/../views/dossiers/create.php';
}

/**
 * ==============================
 * Création d'un dossier (POST)
 * ==============================
 */
function dossier_create(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    if (!requirePost()) {
        header('Location: index.php?action=dossier_create_form');
        exit;
    }

    // 1) Données patient
    $patient = [
        'nom'               => getStrParam('nom'),
        'prenom'            => getStrParam('prenom'),
        'dateNaissance'     => getStrParam('dateNaissance'),
        'adresse'           => getStrParam('adresse'),
        'telephone'         => getStrParam('telephone'),
        'email'             => getStrParam('email'),
        'genre'             => getStrParam('genre', 'Homme'),
        'numeroCarteVitale' => getStrParam('numeroCarteVitale'),
        'mutuelle'          => getStrParam('mutuelle'),
    ];

    if ($patient['nom'] === '' || $patient['prenom'] === '' || $patient['dateNaissance'] === '') {
        $error = "Nom, prénom et date de naissance sont obligatoires.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // 2) Données dossier
    $dossier = [
        'idHopital'         => getIntParam('idHopital', 0),
        'dateAdmission'     => getStrParam('dateAdmission', date('Y-m-d')),
        'historiqueMedical' => getStrParam('historiqueMedical'),
        'antecedant'        => getStrParam('antecedant'),
        'etat_entree'       => getStrParam('etat_entree'),
        'diagnostic'        => getStrParam('diagnostic'),
        'examen'            => getStrParam('examen'),
        'traitements'       => getStrParam('traitements'),
        'statut'            => getStrParam('statut', 'ouvert'),
        'niveau'            => getStrParam('niveau', '1'),
        'delaiPriseCharge'  => getStrParam('delaiPriseCharge', 'NonImmediat'),
        'idTransfert'       => getIntParam('idTransfert', 0),
    ];

    if ($dossier['idHopital'] <= 0) {
        $error = "idHopital manquant.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    $newDossierId = createPatientAndDossier($patient, $dossier);

    header('Location: index.php?action=dossier_detail&id=' . $newDossierId);
    exit;
}

/* ==================================================
   ACTIONS MEDECIN
================================================== */

function dossier_detail_medecin(): void
{
    requireRole('MEDECIN');

    $idDossier = getIntParam('id', 0);
    if ($idDossier <= 0) {
        abort(400, "Paramètre id invalide.");
    }

    $dossier = getDossierById($idDossier);
    if (!$dossier) {
        abort(404, "Dossier introuvable.");
    }

    require_once APP_PATH . '/models/EquipementModel.php';

    // Équipements réservés pour ce dossier
    $equipementsReserves = gestion_equipements_by_dossier($idDossier);

    // Examens demandés pour ce dossier
    $examens = examens_get_by_dossier($idDossier);

    //  Transferts du patient (historique)
    $idPatient = (int)($dossier['idPatient'] ?? 0);
    $transferts = ($idPatient > 0) ? transferts_get_by_patient($idPatient) : [];

    require APP_PATH . '/views/dossiers/detail_medecin.php';
}

function dossier_demander_examen(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

    $idDossier   = getIntParam('idDossier', 0);
    $typeExamen  = getStrParam('typeExamen');
    $noteMedecin = getStrParam('noteMedecin');

    if ($idDossier <= 0 || $typeExamen === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    // Si vide => NULL (أفضل في DB)
    $note = ($noteMedecin === '') ? null : $noteMedecin;

    $ok = examen_create($idDossier, $typeExamen, $note);

    if ($ok) {
        $_SESSION['flash_success'] = "Examen demandé avec succès.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de la demande d'examen.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}

function dossier_demander_transfert(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

    $idDossier    = getIntParam('idDossier', 0);
    $hopitalCible = getStrParam('hopitalCible');
    $motif        = getStrParam('motif');

    if ($idDossier <= 0 || $hopitalCible === '' || $motif === '') {
        $_SESSION['flash_error'] = "Veuillez remplir tous les champs.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    $ok = transfert_create($idDossier, $hopitalCible, $motif);

    if ($ok) {
        $_SESSION['flash_success'] = "Demande de transfert envoyée.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de la demande de transfert.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}