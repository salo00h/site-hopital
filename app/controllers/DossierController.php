<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DOSSIER CONTROLLER (MVC)
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - récupérer les données de la requête ;
| - valider les entrées utilisateur ;
| - appeler les fonctions du modèle ;
| - charger la vue adaptée ;
| - ne jamais contenir de requêtes SQL.
|
| Ce fichier est volontairement organisé par rôle métier :
| 1. Outils communs
| 2. Actions infirmier / infirmier d'accueil
| 3. Actions médecin
|--------------------------------------------------------------------------
*/

use PDOException;

require_once APP_PATH . '/includes/auth_guard.php';

require_once __DIR__ . '/../models/DossierModel.php';
require_once __DIR__ . '/../models/PatientModel.php';

/*
|--------------------------------------------------------------------------
| Modèles utilisés par les actions médicales
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/TransfertModel.php';


/* ======================================================================
   OUTILS COMMUNS
   ====================================================================== */

/**
 * Arrête immédiatement l'exécution avec un code HTTP et un message.
 */
function abort(int $status, string $message): void
{
    http_response_code($status);
    echo $message;
    exit;
}

/**
 * Vérifie que la requête HTTP est bien en POST.
 */
function requirePost(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        abort(405, "Méthode non autorisée.");
        return false;
    }

    return true;
}

/**
 * Récupère un paramètre entier depuis POST ou GET.
 */
function getIntParam(string $key, int $default = 0): int
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return (int)($_POST[$key] ?? $default);
    }

    return (int)($_GET[$key] ?? $default);
}

/**
 * Récupère une chaîne nettoyée depuis POST ou GET.
 */
function getStrParam(string $key, string $default = ''): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return trim((string)($_POST[$key] ?? $default));
    }

    return trim((string)($_GET[$key] ?? $default));
}

/**
 * Normalise une date/heure HTML vers le format attendu par la base.
 */
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

/**
 * Calcule le délai de prise en charge à partir du niveau de tri.
 */
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


/* ======================================================================
   ACTIONS INFIRMIER / INFIRMIER D'ACCUEIL
   ====================================================================== */

/**
 * Liste des dossiers selon le rôle autorisé.
 */
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
    | Données complémentaires pour enrichir la vue
    |--------------------------------------------------------------------------
    | Ces tableaux servent à afficher :
    | - le résumé des équipements ;
    | - le nombre d'examens ;
    | - le nombre de transferts ;
    | - le dernier statut de transfert.
    |--------------------------------------------------------------------------
    */
    $equipementsResume = [];
    $examensCount = [];
    $transfertsCount = [];
    $transfertsLastStatut = [];

    /*
    |--------------------------------------------------------------------------
    | Chargement supplémentaire pour médecin et infirmier
    |--------------------------------------------------------------------------
    */
    if (in_array($role, ['MEDECIN', 'INFIRMIER'], true)) {
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
        $transfertsLastStatut = transferts_last_statut_by_patients($idsPatients);
    }

    require __DIR__ . '/../views/dossiers/liste.php';
}

/**
 * Détail d'un dossier côté infirmier.
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
    |--------------------------------------------------------------------------
    | Chargement des modèles nécessaires à l'affichage complet du dossier
    |--------------------------------------------------------------------------
    */
    require_once APP_PATH . '/models/EquipementModel.php';
    require_once APP_PATH . '/models/TransfertModel.php';

    /*
    |--------------------------------------------------------------------------
    | Données associées au dossier
    |--------------------------------------------------------------------------
    */
    $examens = examens_get_by_dossier($id);
    $equipementsReserves = gestion_equipements_by_dossier($id);

    $idPatient = (int)($dossier['idPatient'] ?? 0);
    $transferts = ($idPatient > 0) ? transferts_get_by_patient($idPatient) : [];

    require __DIR__ . '/../views/dossiers/detail_infirmier.php';
}

/**
 * Formulaire d'édition d'un dossier.
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
 * Mise à jour d'un dossier par l'infirmier d'accueil.
 */
function dossier_update(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);
    $idPatient = getIntParam('idPatient', 0);

    /*
    |--------------------------------------------------------------------------
    | Vérification des identifiants principaux
    |--------------------------------------------------------------------------
    */
    if ($idDossier <= 0 || $idPatient <= 0) {
        abort(400, "IDs invalides");
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification des champs obligatoires patient
    |--------------------------------------------------------------------------
    */
    $nom = getStrParam('nom');
    $prenom = getStrParam('prenom');
    $dateNaissance = getStrParam('dateNaissance');

    if ($nom === '' || $prenom === '' || $dateNaissance === '') {
        $dossier = getDossierById($idDossier);
        $error = "Nom / Prénom / Date naissance obligatoires.";
        require __DIR__ . '/../views/dossiers/edit.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification métier simple sur la date de naissance
    |--------------------------------------------------------------------------
    */
    $timestamp = strtotime($dateNaissance);
    $minDate = strtotime('1900-01-01');
    $today = strtotime(date('Y-m-d'));

    if ($timestamp === false || $timestamp < $minDate || $timestamp > $today) {
        $dossier = getDossierById($idDossier);
        $error = "Date de naissance invalide. Entrez une date réelle entre 1900 et aujourd'hui.";
        require __DIR__ . '/../views/dossiers/edit.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Données patient
    |--------------------------------------------------------------------------
    */
    $adresse = getStrParam('adresse');
    $telephone = getStrParam('telephone');
    $email = getStrParam('email');
    $genre = getStrParam('genre', 'Homme');
    $numeroCarteVitale = getStrParam('numeroCarteVitale');
    $mutuelle = getStrParam('mutuelle');

    /*
    |--------------------------------------------------------------------------
    | Données dossier
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Mise à jour patient
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Mise à jour dossier
    |--------------------------------------------------------------------------
    */
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
 * Formulaire de création d'un dossier.
 */
function dossier_create_form(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    $error = '';
    require __DIR__ . '/../views/dossiers/create.php';
}

/**
 * Création d'un patient et de son dossier.
 */
function dossier_create(): void
{
    requireRole('INFIRMIER_ACCUEIL');

    if (!requirePost()) {
        header('Location: index.php?action=dossier_create_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Données patient
    |--------------------------------------------------------------------------
    */
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

    if ($patient['numeroCarteVitale'] === '') {
        $error = "Le numéro de carte vitale est obligatoire.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    if ($patient['nom'] === '' || $patient['prenom'] === '' || $patient['dateNaissance'] === '') {
        $error = "Nom, prénom et date de naissance sont obligatoires.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification de cohérence de la date de naissance
    |--------------------------------------------------------------------------
    */
    $timestamp = strtotime($patient['dateNaissance']);
    $minDate = strtotime('1900-01-01');
    $today = strtotime(date('Y-m-d'));

    if ($timestamp === false || $timestamp < $minDate || $timestamp > $today) {
        $error = "Date de naissance invalide.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Données dossier
    |--------------------------------------------------------------------------
    */
    $niveau = getStrParam('niveau', '1');
    $delaiPriseCharge = getDelaiPriseEnChargeByNiveau($niveau);

    require_once APP_PATH . '/models/LitModel.php';

    $idPersonnel = (int)($_SESSION['user']['idPersonnel'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | On conserve l'infirmier d'accueil créateur du dossier
    |--------------------------------------------------------------------------
    */
    $idInfirmierAccueil = getInfirmierIdByPersonnel($idPersonnel);

    $dossier = [
        'idHopital'          => getIntParam('idHopital', 0),
        'idInfirmierAccueil' => $idInfirmierAccueil,
        'dateAdmission'      => normalizeDateTime(getStrParam('dateAdmission')),
        'dateSortie'         => normalizeDateTime(getStrParam('dateSortie')),
        'historiqueMedical'  => getStrParam('historiqueMedical'),
        'antecedant'         => getStrParam('antecedant'),
        'etat_entree'        => getStrParam('etat_entree_patient'),
        'diagnostic'         => getStrParam('diagnostic'),
        'traitements'        => getStrParam('traitements'),
        'statut'             => 'ouvert',
        'niveau'             => $niveau,
        'delaiPriseCharge'   => $delaiPriseCharge,
        'idTransfert'        => getIntParam('idTransfert', 0),
    ];

    if ($dossier['idHopital'] <= 0) {
        $error = "Hôpital manquant.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    if ($dossier['idInfirmierAccueil'] <= 0) {
        $error = "Infirmier d'accueil introuvable.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Création en base
    |--------------------------------------------------------------------------
    */
    try {
        $newDossierId = createPatientAndDossier($patient, $dossier);

        header('Location: index.php?action=dossier_detail&id=' . $newDossierId);
        exit;
    } catch (PDOException $e) {
        $msg = $e->getMessage();

        /*
        ----------------------------------------------------------------------
        | Gestion ciblée de certaines erreurs SQL
        ----------------------------------------------------------------------
        */
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

/**
 * Confirmation de l'installation du patient dans un lit.
 */
function confirmer_installation_patient(): void
{
    requireRole('INFIRMIER');

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

    /*
    |--------------------------------------------------------------------------
    | Vérification de la présence d'un lit attribué
    |--------------------------------------------------------------------------
    */
    if (empty($dossier['idLit'])) {
        $_SESSION['flash_error'] = "Aucun lit attribué à ce dossier.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Le lit doit être réservé avant confirmation d'installation
    |--------------------------------------------------------------------------
    */
    if (($dossier['etatLit'] ?? '') !== 'reserve') {
        $_SESSION['flash_error'] = "Le lit doit être à l'état réservé avant confirmation.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    $idLit = (int)$dossier['idLit'];

    require_once APP_PATH . '/models/LitModel.php';

    $idPersonnel = (int)($_SESSION['user']['idPersonnel'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | On conserve l'infirmier qui confirme l'installation
    |--------------------------------------------------------------------------
    */
    $idInfirmier = getInfirmierIdByPersonnel($idPersonnel);

    /*
    |--------------------------------------------------------------------------
    | Le modèle met à jour :
    | - l'état du lit ;
    | - le statut du dossier ;
    | - l'infirmier ayant confirmé l'installation.
    |--------------------------------------------------------------------------
    */
    confirmInstallationPatient($idDossier, $idLit, $idInfirmier);

    $_SESSION['flash_success'] =
        "Le patient a été installé au lit " . ($dossier['numeroLit'] ?? '') . ". "
        . "Le lit est maintenant occupé. "
        . "Le dossier passe en attente de consultation.";

    $_SESSION['flash_error'] = '';

    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}

/**
 * Confirmation finale de la sortie côté infirmier.
 */
function confirmerSortieInfirmier(): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, ['INFIRMIER', 'INFIRMIER_ACCUEIL'], true)) {
        abort(403, "Accès refusé.");
    }

    if (!requirePost()) {
        return;
    }

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


/* ======================================================================
   ACTIONS MEDECIN
   ====================================================================== */

/**
 * Détail d'un dossier côté médecin.
 */
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

    /*
    |--------------------------------------------------------------------------
    | Données affichées dans la vue médecin
    |--------------------------------------------------------------------------
    */
    $equipementsReserves = gestion_equipements_by_dossier($idDossier);
    $examens = examens_get_by_dossier($idDossier);

    $idPatient = (int)($dossier['idPatient'] ?? 0);
    $transferts = ($idPatient > 0) ? transferts_get_by_patient($idPatient) : [];

    require APP_PATH . '/views/dossiers/detail_medecin.php';
}

/**
 * Demande de transfert initiée par le médecin.
 */
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

/**
 * Début de consultation par le médecin.
 */
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
 * Validation de la sortie médicale.
 */
function validerSortieMedecin(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

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

/**
 * Formulaire de modification du dossier par le médecin.
 * Le médecin modifie uniquement les informations médicales.
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
 * Mise à jour médicale du dossier.
 */
function dossier_update_medecin(): void
{
    requireRole('MEDECIN');

    if (!requirePost()) {
        return;
    }

    $idDossier = getIntParam('idDossier', 0);

    if ($idDossier <= 0) {
        abort(400, "ID invalide.");
    }

    /*
    |--------------------------------------------------------------------------
    | Le médecin ne modifie que les données médicales
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
