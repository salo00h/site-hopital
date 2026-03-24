<?php
declare(strict_types=1);

/*
  ==============================
  LIT CONTROLLER (MVC)
  ==============================
  Rôle :
  - Vérifier l'utilisateur (session)
  - Lire les paramètres (GET/POST)
  - Appeler les fonctions du Model
  - Afficher la View
*/

require_once APP_PATH . '/includes/auth_guard.php';
require_once __DIR__ . '/../models/LitModel.php';
require_once __DIR__ . '/../models/DossierModel.php';


/**
 * Tableau de bord des lits (infirmier d'accueil)
 * - Récupère le service depuis le personnel connecté
 * - Calcule les stats (disponible / occupé / réservé / HS)
 * - Calcule le taux d’occupation
 * - Affiche un message d’alerte si peu de lits disponibles
 */
function lits_dashboard(): void
{
    // Sécurité : utilisateur connecté
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // Sécurité : rôle
    requireRole('INFIRMIER_ACCUEIL');

    // Récupérer le service lié au personnel connecté
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService   = $idPersonnel > 0 ? (int)(getServiceIdByPersonnel($idPersonnel) ?? 0) : 0;

    // Variables pour la vue
    $error = '';
    $stats = [];
    $lits  = [];

    $nbDisponible   = 0;
    $nbOccupe       = 0;
    $nbReserve      = 0;
    $nbHs           = 0;
    $totalLits      = 0;
    $tauxOccupation = 0;

    $alertLevel   = '';
    $alertMessage = '';

    // Si aucun service n'est trouvé
    if ($idService <= 0) {
        $error = "Service introuvable pour cet utilisateur.";
        require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
        return;
    }

    // Récupération des données
    $stats = getLitStatsByService($idService);
    $lits  = getLitsByService($idService);

    // Transformer les stats SQL en tableau simple
    $map = [];
    foreach ($stats as $s) {
        $etat = (string)($s['etatLit'] ?? '');
        $map[$etat] = (int)($s['nb'] ?? 0);
    }

    // États principaux
    $nbDisponible = $map['disponible'] ?? 0;
    $nbOccupe     = $map['occupe'] ?? 0;
    $nbReserve    = $map['reserve'] ?? 0;

    // États HS possibles
    $nbHs  = 0;
    $nbHs += $map['hs'] ?? 0;
    $nbHs += $map['HS'] ?? 0;
    $nbHs += $map['en_panne'] ?? 0;
    $nbHs += $map['maintenance'] ?? 0;

    // Calcul du total
    $totalLits = $nbDisponible + $nbOccupe + $nbReserve + $nbHs;

    // Calcul du taux d’occupation
    if ($totalLits > 0) {
        $tauxOccupation = (int) round((($nbOccupe + $nbReserve) / $totalLits) * 100);
    }

    // Message d’alerte simple
    if ($nbDisponible === 0) {
        $alertLevel = 'danger';
        $alertMessage = "Aucun lit disponible : envisager l'attente ou une demande de transfert.";
    } elseif ($nbDisponible <= 2) {
        $alertLevel = 'warning';
        $alertMessage = "Peu de lits disponibles : prioriser selon le niveau de gravité (triage).";
    }

    require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
}

/**
 * Formulaire de réservation d'un lit
 * - lit disponible uniquement dans le service de l'infirmier
 * - on empêche un dossier de réserver plusieurs lits
 */
function lit_reserver_form(): void
{
    // Sécurité : utilisateur connecté
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // Sécurité : rôle
    requireRole('INFIRMIER_ACCUEIL');

    // Lire idDossier depuis l'URL
    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        echo "idDossier manquant.";
        exit;
    }

    // Vérifier si le dossier a déjà un lit
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Récupérer le dossier
    $dossier = getDossierById($idDossier);
    if (!$dossier) {
        echo "Dossier introuvable.";
        exit;
    }

    // Récupérer l'hôpital du dossier
    $idHopital = (int)($dossier['idHopital'] ?? 0);

    $error = '';
    $availableLits = [];

    if ($idHopital <= 0) {
        $error = "Hôpital introuvable pour ce dossier.";
    } else {
        // Lits disponibles dans l’hôpital
        $availableLits = getAvailableLitsByHopital($idHopital);
    }

    require __DIR__ . '/../views/lits/reserver.php';
}

/**
 * Traitement de la réservation (POST)
 * - double sécurité : re-vérifier que le dossier n'a pas déjà un lit
 * - enregistrer la réservation et rediriger vers détail dossier
 */
function lit_reserver(): void
{
    // ==============================
    // Vérifier que l'utilisateur est connecté
    // ==============================
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // Sécurité : rôle
    requireRole('INFIRMIER_ACCUEIL');


    // ==============================
    // Lire les paramètres envoyés par le formulaire
    // ==============================
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $idLit     = (int)($_POST['idLit'] ?? 0);

    // Dates par défaut si l'utilisateur ne les a pas fournies
    $dateDebut = $_POST['dateDebut'] ?? date('Y-m-d H:i:s');
    $dateFin   = $_POST['dateFin'] ?? date('Y-m-d H:i:s', time() + 2 * 3600);

    // Vérification simple des paramètres
    if ($idDossier <= 0 || $idLit <= 0) {
        echo "Paramètres invalides.";
        exit;
    }

    // ==============================
    // Sécurité : vérifier que le dossier n'a pas déjà un lit
    // ==============================
    $litDeja = getLitForDossier($idDossier);

    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // ==============================
    // Trouver l'infirmier lié au personnel connecté
    // ==============================
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idInfirmier = getInfirmierIdByPersonnel($idPersonnel);

    // Si aucun infirmier n'est trouvé on met NULL
    if (!$idInfirmier) {
        $idInfirmier = null;
    }

    // ==============================
    // Tentative de réservation du lit
    // ==============================
    try {

        reserveLitForDossier(
            $idLit,
            $idDossier,
            $idInfirmier,
            $dateDebut,
            $dateFin
        );

        // Message succès
        $_SESSION['success'] = "Lit réservé avec succès.";

        // Redirection vers le détail du dossier
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {

        // ==============================
        // Gestion d'erreur
        // ==============================
        $error = $e->getMessage();

        // Recharger les lits disponibles pour l'hôpital du dossier
        $dossier = getDossierById($idDossier);
        $idHopital = (int)($dossier['idHopital'] ?? 0);

        $availableLits = $idHopital > 0
            ? getAvailableLitsByHopital($idHopital)
            : [];

        // Réafficher le formulaire avec l'erreur
        require __DIR__ . '/../views/lits/reserver.php';
    }
}



/**
 * Liste des lits pour infirmier.
 * L’infirmier peut changer l’état :
 * - reserve -> occupe
 * - occupe -> disponible
 */
function lits_list_infirmier(): void
{
    requireRole('INFIRMIER');

    $lits = lits_get_all();

    require __DIR__ . '/../views/lits/liste_infirmier.php';
}

/**
 * Changer l’état d’un lit par l’infirmier.
 */
function lit_changer_etat_infirmier(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idLit = (int)($_POST['idLit'] ?? 0);
        $etat  = trim((string)($_POST['etat'] ?? ''));
    } else {
        $idLit = (int)($_GET['idLit'] ?? 0);
        $etat  = trim((string)($_GET['etat'] ?? ''));
    }

    if ($idLit <= 0 || $etat === '') {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=lits_list_infirmier');
        exit;
    }

    $lit = lit_get_by_id($idLit);

    if (!$lit) {
        $_SESSION['flash_error'] = "Lit introuvable.";
        header('Location: index.php?action=lits_list_infirmier');
        exit;
    }

    $etatActuel = (string)($lit['etatLit'] ?? '');
    $allowed = false;

    /*
    |--------------------------------------------------------------------------
    | Gestion des transitions d'état du lit par l'infirmier
    |--------------------------------------------------------------------------
    | L'infirmier peut :
    | - confirmer une réservation : reserve -> occupe
    | - libérer un lit : occupe -> disponible
    | - signaler une panne : vers en_panne
    |
    | Le changement vers "en_panne" est autorisé car l'infirmier
    | peut constater un problème sur le lit pendant l'utilisation.
    */
    if ($etatActuel === 'reserve' && $etat === 'occupe') {
        $allowed = true;
    }

    if ($etatActuel === 'occupe' && $etat === 'disponible') {
        $allowed = true;
    }

    if ($etat === 'en_panne') {
        $allowed = true;
    }

    if (!$allowed) {
        $_SESSION['flash_error'] = "Transition d’état non autorisée.";
        header('Location: index.php?action=lits_list_infirmier');
        exit;
    }

    $ok = lit_update_etat($idLit, $etat);

    if ($ok) {
        if ($etat === 'en_panne') {
            // TODO : envoyer une alerte au service de maintenance
        }

        $_SESSION['flash_success'] = "État du lit mis à jour.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
    }

    header('Location: index.php?action=lits_list_infirmier');
    exit;
}



