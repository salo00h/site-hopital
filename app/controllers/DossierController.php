<?php
declare(strict_types=1);

/*
  ==============================
  DOSSIER CONTROLLER (MVC)
  ==============================
  - Le contrôleur ne contient pas de SQL.
  - Il récupère les données, vérifie, puis appelle le Model.
  - Ensuite il charge la View.
*/
use PDOException;

require_once APP_PATH . '/includes/auth_guard.php';

require_once __DIR__ . '/../models/DossierModel.php';
require_once __DIR__ . '/../models/PatientModel.php';

// Actions médecin
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/TransfertModel.php';


/* ==================================================
   FONCTIONS UTILES
================================================== */

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return (int)($_POST[$key] ?? $default);
    }

    return (int)($_GET[$key] ?? $default);
}

function getStrParam(string $key, string $default = ''): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return trim((string)($_POST[$key] ?? $default));
    }

    return trim((string)($_GET[$key] ?? $default));
}


function normalizeDateTime(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $value = str_replace('T', ' ', $value);

    if (strlen($value) === 16) {
        $value .= ':00';
    }

    return $value;
}



function getDelaiPriseEnChargeByNiveau(string $niveau): string
{
    return match ($niveau) {
        '1' => '0',
        '2' => '10',
        '3' => '30',
        '4', '5' => 'NonImmediat',
        default => '0',
    };
}
/* ==================================================
   ACTIONS INFIRMIER
================================================== */

function dossiers_list(): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, ['INFIRMIER_ACCUEIL', 'INFIRMIER', 'MEDECIN'], true)) {
        abort(403, "Accès refusé.");
    }

    $q = getStrParam('q', '');
    $dossiers = getAllDossiers($q);

    /*
    |--------------------------------------------------------------------------
    | Initialisation des variables
    |--------------------------------------------------------------------------
    | Ces variables servent à enrichir l'affichage dans la vue :
    | - résumé des équipements
    | - nombre d'examens
    | - nombre de transferts
    | - dernier statut de transfert
    */
    $equipementsResume = [];
    $examensCount = [];
    $transfertsCount = [];
    $transfertsLastStatut = [];

    /*
    |--------------------------------------------------------------------------
    | Chargement des données supplémentaires (médecin + infirmier)
    |--------------------------------------------------------------------------
    */
    if (in_array($role, ['MEDECIN', 'INFIRMIER'], true)) {
        require_once APP_PATH . '/models/EquipementModel.php';
        require_once APP_PATH . '/models/TransfertModel.php';

        // Récupération des IDs des dossiers
        $idsDossiers = array_map(
            static fn($d) => (int)$d['idDossier'],
            $dossiers
        );

        // Résumé des équipements + nombre d'examens
        $equipementsResume = equipements_resume_par_dossier($idsDossiers);
        $examensCount = examens_count_by_dossiers($idsDossiers);

        // Récupération des IDs patients uniques
        $idsPatients = array_values(array_unique(array_filter(array_map(
            static fn($d) => (int)($d['idPatient'] ?? 0),
            $dossiers
        ))));

        // Nombre de transferts + dernier statut
        $transfertsCount = transferts_count_by_patients($idsPatients);
        $transfertsLastStatut = transferts_last_statut_by_patients($idsPatients);
    }

    require __DIR__ . '/../views/dossiers/liste.php';
}

/**
 * Détail d'un dossier infirmier
 */
function dossier_detail(): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, ['INFIRMIER_ACCUEIL', 'INFIRMIER'], true)) {
        abort(403, "Accès refusé.");
    }

    $id = getIntParam('id', 0);

    if ($id <= 0) {
        abort(400, "ID dossier invalide");
    }

    $dossier = getDossierById($id);

    if (!$dossier) {
        abort(404, "Dossier introuvable");
    }

    /*
      Chargement des modèles nécessaires
      pour afficher toutes les informations
      liées au dossier infirmier.
    */
    require_once APP_PATH . '/models/EquipementModel.php';
    require_once APP_PATH . '/models/TransfertModel.php';

    /*
      Récupération des examens liés au dossier.
    */
    $examens = examens_get_by_dossier($id);

    /*
      Récupération des équipements réservés
      pour ce dossier.
    */
    $equipementsReserves = gestion_equipements_by_dossier($id);

    /*
      Récupération des transferts liés au patient
      si l'identifiant patient existe.
    */
    $idPatient = (int)($dossier['idPatient'] ?? 0);
    $transferts = ($idPatient > 0) ? transferts_get_by_patient($idPatient) : [];

    require __DIR__ . '/../views/dossiers/detail_infirmier.php';
}

/**
 * Formulaire d'édition
 */
function dossier_edit_form(): void
{
    requireRole('INFIRMIER_ACCUEIL');

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
 * Mise à jour d'un dossier
 */
function dossier_update(): void
{
    requireRole('INFIRMIER_ACCUEIL');
    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);
    $idPatient = getIntParam('idPatient', 0);

    // Vérification des identifiants
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

    // Vérification simple de la date de naissance
    $timestamp = strtotime($dateNaissance);
    $minDate = strtotime('1900-01-01');
    $today = strtotime(date('Y-m-d'));

    if ($timestamp === false || $timestamp < $minDate || $timestamp > $today) {
        $dossier = getDossierById($idDossier);
        $error = "Date de naissance invalide. Entrez une date réelle entre 1900 et aujourd'hui.";
        require __DIR__ . '/../views/dossiers/edit.php';
        return;
    }

    // Données patient
    $adresse = getStrParam('adresse');
    $telephone = getStrParam('telephone');
    $email = getStrParam('email');
    $genre = getStrParam('genre', 'Homme');
    $numeroCarteVitale = getStrParam('numeroCarteVitale');
    $mutuelle = getStrParam('mutuelle');

    // Données dossier
    $dateAdmission = normalizeDateTime(getStrParam('dateAdmission'));
    $dateSortie = normalizeDateTime(getStrParam('dateSortie'));
    $historiqueMedical = getStrParam('historiqueMedical');
    $antecedant = getStrParam('antecedant');
    $etat_entree = getStrParam('etat_entree_patient');
    $diagnostic = getStrParam('diagnostic');
    $traitements = getStrParam('traitements');

    $statut = getStrParam('statut', 'ouvert');
    $niveau = getStrParam('niveau', '1');
    $delai = getStrParam('delaiPriseCharge', 'NonImmediat');

    // Mise à jour patient
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

    // Mise à jour dossier
    updateDossier(
        $idDossier,
        $dateAdmission,
        $dateSortie,
        $historiqueMedical,
        $antecedant,
        $etat_entree,
        $diagnostic,
        $traitements,
        $statut,
        $niveau,
        $delai
    );

    $_SESSION['flash_success'] = "Dossier modifié avec succès.";
    $_SESSION['flash_error'] = "";

    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}

/**
 * Formulaire de création
 */
function dossier_create_form(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    $error = '';
    require __DIR__ . '/../views/dossiers/create.php';
}

/**
 * Création d'un dossier
 */
function dossier_create(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    if (!requirePost()) {
        header('Location: index.php?action=dossier_create_form');
        exit;
    }

    // ==============================
    // Données patient
    // ==============================
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

    // Vérification carte vitale obligatoire
    if ($patient['numeroCarteVitale'] === '') {
        $error = "Le numéro de carte vitale est obligatoire.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // Vérification champs obligatoires
    if ($patient['nom'] === '' || $patient['prenom'] === '' || $patient['dateNaissance'] === '') {
        $error = "Nom, prénom et date de naissance sont obligatoires.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // Vérification date de naissance
    $timestamp = strtotime($patient['dateNaissance']);
    $minDate   = strtotime('1900-01-01');
    $today     = strtotime(date('Y-m-d'));

    if ($timestamp === false || $timestamp < $minDate || $timestamp > $today) {
        $error = "Date de naissance invalide.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // ==============================
    // Données dossier
    // ==============================
    $niveau = getStrParam('niveau', '1');
    $delaiPriseCharge = getDelaiPriseEnChargeByNiveau($niveau);

    $dossier = [
        'idHopital'         => getIntParam('idHopital', 0),
        'dateAdmission'     => normalizeDateTime(getStrParam('dateAdmission')),
        'dateSortie'        => normalizeDateTime(getStrParam('dateSortie')),
        'historiqueMedical' => getStrParam('historiqueMedical'),
        'antecedant'        => getStrParam('antecedant'),
        'etat_entree'       => getStrParam('etat_entree_patient'),
        'diagnostic'        => getStrParam('diagnostic'),
        'traitements'       => getStrParam('traitements'),
        'statut'            => 'ouvert',
        'niveau'            => $niveau,
        'delaiPriseCharge'  => $delaiPriseCharge,
        'idTransfert'       => getIntParam('idTransfert', 0),
    ];

    // Vérifier hôpital
    if ($dossier['idHopital'] <= 0) {
        $error = "Hôpital manquant.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // ==============================
    // Création
    // ==============================
    try {
        $newDossierId = createPatientAndDossier($patient, $dossier);

        header('Location: index.php?action=dossier_detail&id=' . $newDossierId);
        exit;

    } catch (PDOException $e) {
        $msg = $e->getMessage();

        // Gestion erreurs spécifiques base de données
        if (
            str_contains($msg, 'numeroCarteVitale') ||
            str_contains($msg, 'PATIENT.numeroCarteVitale') ||
            str_contains($msg, "for key 'numeroCarteVitale'") ||
            str_contains($msg, "for key 'patient.numeroCarteVitale'")
        ) {
            $error = "Ce numéro de carte vitale existe déjà.";
        } elseif (
            str_contains($msg, 'Duplicate entry') &&
            str_contains($msg, 'idPatient')
        ) {
            $error = "Ce patient possède déjà un dossier.";
        } else {
            $error = "Erreur lors de la création.";
        }

        require __DIR__ . '/../views/dossiers/create.php';
        return;

    } catch (Throwable $e) {
        $error = "Erreur inattendue.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }
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

    // Équipements réservés
    $equipementsReserves = gestion_equipements_by_dossier($idDossier);

    // Examens demandés
    $examens = examens_get_by_dossier($idDossier);

    // Transferts du patient
    $idPatient = (int)($dossier['idPatient'] ?? 0);
    $transferts = ($idPatient > 0) ? transferts_get_by_patient($idPatient) : [];

    require APP_PATH . '/views/dossiers/detail_medecin.php';
}



function dossier_demander_transfert(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);
    $hopitalCible = getStrParam('hopitalCible');
    $motif = getStrParam('motif');

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

function dossier_commencer_consultation(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);

    if ($idDossier <= 0) {
        $_SESSION['flash_error'] = "Dossier invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $dossier = getDossierById($idDossier);

    if (!$dossier) {
        $_SESSION['flash_error'] = "Dossier introuvable.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    if (!in_array(($dossier['statut'] ?? ''), ['attente_consultation', 'attente_resultat'], true)) {
        $_SESSION['flash_error'] = "Le dossier doit être en attente de consultation.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    dossier_update_statut($idDossier, 'consultation');

    $_SESSION['flash_success'] = "La consultation a commencé. Le dossier passe en consultation.";
    $_SESSION['flash_error'] = '';

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}


/**
 * ==================================================
 * CONTROLLER : confirmer_installation_patient
 * ==================================================
 * Rôle :
 * Cette fonction permet à l’infirmier de confirmer
 * que le patient a bien été installé dans un lit.
 *
 * ⚠️ Remarque :
 * Ce code a été amélioré et structuré avec l’aide
 * d’une assistance en intelligence artificielle (IA),
 * tout en respectant les bonnes pratiques du cours.
 * ==================================================
 */
function confirmer_installation_patient(): void
{
    // Vérifie que l'utilisateur a le rôle INFIRMIER
    requireRole('INFIRMIER');

    // Vérifie que la requête est bien en POST (sécurité)
    if (!requirePost()) {
        return;
    }

    // Récupération sécurisée de l'id du dossier depuis POST
    $idDossier = getIntParam('idDossier', 0);

    // Vérification : id invalide
    if ($idDossier <= 0) {
        $_SESSION['flash_error'] = "Dossier invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    // Récupération du dossier depuis la base de données
    $dossier = getDossierById($idDossier);

    // Vérification : dossier existe
    if (!$dossier) {
        $_SESSION['flash_error'] = "Dossier introuvable.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    // Vérification : un lit est bien attribué
    if (empty($dossier['idLit'])) {
        $_SESSION['flash_error'] = "Aucun lit attribué à ce dossier.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Vérification métier :
    // Le lit doit être "reserve" avant confirmation
    if (($dossier['etatLit'] ?? '') !== 'reserve') {
        $_SESSION['flash_error'] = "Le lit doit être à l'état réservé avant confirmation.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Récupération de l'id du lit
    $idLit = (int)$dossier['idLit'];

    // Appel du Model :
    // - Passage du lit à "occupé"
    // - Mise à jour du statut du dossier
    confirmInstallationPatient($idDossier, $idLit);

    // Message de succès affiché à l'utilisateur
    $_SESSION['flash_success'] =
        "Le patient a été installé au lit " . ($dossier['numeroLit'] ?? '') . ". "
        . "Le lit est maintenant occupé. "
        . "Le dossier passe en attente de consultation.";

    // Nettoyage des anciens messages d'erreur
    $_SESSION['flash_error'] = '';

    // Redirection vers la page détail du dossier
    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}


function validerSortieMedecin(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) return;

    $idDossier = getIntParam('idDossier', 0);

    if ($idDossier <= 0) {
        $_SESSION['flash_error'] = 'Dossier invalide.';
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    validerSortieMedicale($idDossier);

    $_SESSION['flash_success'] = 'Sortie validée par le médecin.';
    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}


function confirmerSortieInfirmier(): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, ['INFIRMIER', 'INFIRMIER_ACCUEIL'], true)) {
        abort(403, "Accès refusé.");
    }

    if (!requirePost()) return;

    $idDossier = getIntParam('idDossier', 0);

    if ($idDossier <= 0) {
        $_SESSION['flash_error'] = 'Dossier invalide.';
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    confirmerSortieFinale($idDossier);

    $_SESSION['flash_success'] = 'Sortie finale confirmée. Lit et équipements libérés.';
    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}




/**
 * ==================================================
 * FORMULAIRE MODIFICATION DOSSIER (MEDECIN)
 * ==================================================
 * Le médecin peut modifier uniquement
 * les informations médicales du dossier.
 */
function dossier_edit_medecin_form(): void
{
    requireRole('MEDECIN');

    $id = getIntParam('id', 0);

    if ($id <= 0) {
        abort(400, "ID invalide.");
    }

    $dossier = getDossierById($id);

    if (!$dossier) {
        abort(404, "Dossier introuvable.");
    }

    $error = '';
    require __DIR__ . '/../views/dossiers/edit_medecin.php';
}


/**
 * ==================================================
 * UPDATE DOSSIER (MEDECIN)
 * ==================================================
 */
function dossier_update_medecin(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) return;

    $idDossier = getIntParam('idDossier', 0);

    if ($idDossier <= 0) {
        abort(400, "ID invalide.");
    }

    /*
    |--------------------------------------------------------------------------
    | Le médecin modifie uniquement les données médicales
    |--------------------------------------------------------------------------
    */
    $etat_entree = getStrParam('etat_entree');
    $diagnostic = getStrParam('diagnostic');
    $traitements = getStrParam('traitements');
    $historiqueMedical = getStrParam('historiqueMedical');
    $antecedant = getStrParam('antecedant');

    $dossier = getDossierById($idDossier);

    if (!$dossier) {
        abort(404, "Dossier introuvable.");
    }

    updateDossier(
        $idDossier,
        $dossier['dateAdmission'],
        $dossier['dateSortie'],
        $historiqueMedical,
        $antecedant,
        $etat_entree,
        $diagnostic,
        $traitements,
        $dossier['statut'],
        $dossier['niveau'],
        $dossier['delaiPriseCharge']
    );

    $_SESSION['flash_success'] = "Dossier médical mis à jour.";

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}
