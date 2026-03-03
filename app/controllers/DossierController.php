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

/**
 * Liste des dossiers (avec recherche simple via ?q=...)
 */
/**
 * Affiche la liste des dossiers.
 * Pour le rôle MEDECIN uniquement :
 * on récupère un résumé des équipements associés
 * afin de les afficher dans la liste.
 */
function dossiers_list(): void
{
    // Récupération du filtre de recherche
    $q = getStrParam('q', '');

    // Récupération des dossiers depuis le Model
    $dossiers = getAllDossiers($q);

    // Initialisation du résumé des équipements
    $equipementsResume = [];

    // Uniquement pour le MEDECIN : afficher les équipements liés
    if (($_SESSION['user']['role'] ?? '') === 'MEDECIN') {

        require_once APP_PATH . '/models/EquipementModel.php';

        // Extraction des idDossier pour requête groupée
        $ids = array_map(
            static fn($d) => (int)$d['idDossier'],
            $dossiers
        );

        // Récupération des équipements associés à chaque dossier
        $equipementsResume = equipements_resume_par_dossier($ids);
    }

    // Envoi des données à la vue
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
        return;
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        abort(404, "Dossier introuvable");
        return;
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
        return;
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        abort(404, "Dossier introuvable");
        return;
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
        return;
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
 * Affichage du formulaire de création
 * ==============================
 * - Accessible uniquement par l'infirmier
 * - Le médecin n'a pas le droit de créer un dossier
 */
function dossier_create_form(): void
{
    // Sécurité : seul le rôle INFIRMIER peut accéder
    requireRole('INFIRMIER_ACCUEIL');

    $error = '';
    require __DIR__ . '/../views/dossiers/create.php';
}

/**
 * ==============================
 * Traitement de la création d'un dossier (POST)
 * ==============================
 * - Vérifie le rôle utilisateur
 * - Vérifie les champs obligatoires
 * - Crée le patient puis le dossier (transaction)
 */
function dossier_create(): void
{
    // Sécurité : seul l'infirmier peut créer
    requireRole('INFIRMIER_ACCUEIL');

    // Vérifie que la requête est bien en POST
    if (!requirePost()) {
        header('Location: index.php?action=dossier_create_form');
        exit;
    }

    /* =========================================
       1) Récupération des données du patient
    ========================================== */
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

    // Vérification des champs obligatoires
    if ($patient['nom'] === '' ||
        $patient['prenom'] === '' ||
        $patient['dateNaissance'] === '') {

        $error = "Nom, prénom et date de naissance sont obligatoires.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    /* =========================================
       2) Récupération des données du dossier
    ========================================== */
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

    // Vérification minimale
    if ($dossier['idHopital'] <= 0) {
        $error = "idHopital manquant.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    /* =========================================
       3) Création en base (transaction sécurisée)
       - Création du patient
       - Création du dossier lié
    ========================================== */
    $newDossierId = createPatientAndDossier($patient, $dossier);

    // Redirection vers le détail du dossier
    header('Location: index.php?action=dossier_detail&id=' . $newDossierId);
    exit;
}
// ===============================
// ACTIONS MEDECIN
// ===============================

function dossier_detail_medecin(): void
{
    requireRole('MEDECIN');

    $idDossier = getIntParam('id', 0);
    if ($idDossier <= 0) {
        abort(400, "Paramètre id invalide.");
        return;
    }

    $dossier = getDossierById($idDossier);
    if (!$dossier) {
        abort(404, "Dossier introuvable.");
        return;
    }

    require_once APP_PATH . '/models/EquipementModel.php';

    // Récupération des équipements liés au dossier pour affichage dans la vue
    $equipementsReserves = gestion_equipements_by_dossier($idDossier);

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

    $ok = examen_create($idDossier, $typeExamen, $noteMedecin);

    $_SESSION['flash_success'] = $ok ? "Examen demandé avec succès." : "";
    $_SESSION['flash_error']   = $ok ? "" : "Erreur lors de la demande d'examen.";

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

    $_SESSION['flash_success'] = $ok ? "Demande de transfert envoyée." : "";
    $_SESSION['flash_error']   = $ok ? "" : "Erreur lors de la demande de transfert.";

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}