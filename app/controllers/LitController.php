<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LIT CONTROLLER (MVC)
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - vérifier l'utilisateur et son rôle ;
| - lire les paramètres GET / POST ;
| - appeler les fonctions du modèle ;
| - préparer les données pour la vue ;
| - afficher la vue adaptée.
|
| Organisation du fichier :
| 1. Chargements communs
| 2. Actions infirmier d'accueil
| 3. Actions infirmier
| 4. Actions technicien
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/includes/auth_guard.php';
require_once __DIR__ . '/../models/LitModel.php';
require_once __DIR__ . '/../models/DossierModel.php';


/* ======================================================================
   ACTIONS INFIRMIER D'ACCUEIL
   ====================================================================== */

/**
 * Tableau de bord des lits pour l'infirmier d'accueil.
 *
 * Cette action :
 * - récupère le service du personnel connecté ;
 * - calcule les statistiques des lits ;
 * - calcule le taux d'occupation ;
 * - prépare un message d'alerte si peu de lits sont disponibles.
 */
function lits_dashboard(): void
{
    /*
    |--------------------------------------------------------------------------
    | Vérification : utilisateur connecté
    |--------------------------------------------------------------------------
    */
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification : rôle autorisé
    |--------------------------------------------------------------------------
    */
    requireRole('INFIRMIER_ACCUEIL');

    /*
    |--------------------------------------------------------------------------
    | Récupération du service lié au personnel connecté
    |--------------------------------------------------------------------------
    */
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService = $idPersonnel > 0 ? (int)(getServiceIdByPersonnel($idPersonnel) ?? 0) : 0;

    /*
    |--------------------------------------------------------------------------
    | Initialisation des variables utilisées par la vue
    |--------------------------------------------------------------------------
    */
    $error = '';
    $stats = [];
    $lits = [];

    $nbDisponible = 0;
    $nbOccupe = 0;
    $nbReserve = 0;
    $nbHs = 0;
    $totalLits = 0;
    $tauxOccupation = 0;

    $alertLevel = '';
    $alertMessage = '';

    /*
    |--------------------------------------------------------------------------
    | Cas d'erreur : aucun service trouvé pour l'utilisateur
    |--------------------------------------------------------------------------
    */
    if ($idService <= 0) {
        $error = "Service introuvable pour cet utilisateur.";
        require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération des statistiques et de la liste des lits
    |--------------------------------------------------------------------------
    */
    $stats = getLitStatsByService($idService);
    $lits = getLitsByService($idService);

    /*
    |--------------------------------------------------------------------------
    | Transformation des statistiques SQL en tableau simple
    |--------------------------------------------------------------------------
    */
    $map = [];
    foreach ($stats as $s) {
        $etat = (string)($s['etatLit'] ?? '');
        $map[$etat] = (int)($s['nb'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | États principaux
    |--------------------------------------------------------------------------
    */
    $nbDisponible = $map['disponible'] ?? 0;
    $nbOccupe = $map['occupe'] ?? 0;
    $nbReserve = $map['reserve'] ?? 0;

    /*
    |--------------------------------------------------------------------------
    | États assimilés à un lit indisponible / hors service
    |--------------------------------------------------------------------------
    */
    $nbHs = 0;
    $nbHs += $map['hs'] ?? 0;
    $nbHs += $map['HS'] ?? 0;
    $nbHs += $map['en_panne'] ?? 0;
    $nbHs += $map['maintenance'] ?? 0;

    /*
    |--------------------------------------------------------------------------
    | Calcul du total des lits
    |--------------------------------------------------------------------------
    */
    $totalLits = $nbDisponible + $nbOccupe + $nbReserve + $nbHs;

    /*
    |--------------------------------------------------------------------------
    | Calcul du taux d'occupation
    |--------------------------------------------------------------------------
    */
    if ($totalLits > 0) {
        $tauxOccupation = (int)round((($nbOccupe + $nbReserve) / $totalLits) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | Message d'alerte simple selon le nombre de lits disponibles
    |--------------------------------------------------------------------------
    */
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
 * Afficher le formulaire de réservation d'un lit.
 *
 * Règles métier :
 * - seuls les lits disponibles du service de l'infirmier d'accueil sont affichés ;
 * - un dossier ne peut pas avoir plusieurs lits réservés.
 */
function lit_reserver_form(): void
{
    /*
    |--------------------------------------------------------------------------
    | Vérification : utilisateur connecté
    |--------------------------------------------------------------------------
    */
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification : rôle autorisé
    |--------------------------------------------------------------------------
    */
    requireRole('INFIRMIER_ACCUEIL');

    /*
    |--------------------------------------------------------------------------
    | Lecture de l'identifiant du dossier
    |--------------------------------------------------------------------------
    */
    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        echo "idDossier manquant.";
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification : un dossier ne doit pas déjà avoir un lit
    |--------------------------------------------------------------------------
    */
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération du dossier
    |--------------------------------------------------------------------------
    */
    $dossier = getDossierById($idDossier);
    if (!$dossier) {
        echo "Dossier introuvable.";
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Le délai est récupéré pour affichage ou logique de réservation
    |--------------------------------------------------------------------------
    */
    $delai = $dossier['delaiPriseCharge'] ?? '0';

    /*
    |--------------------------------------------------------------------------
    | Récupération du service de l'infirmier connecté
    |--------------------------------------------------------------------------
    */
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService = $idPersonnel > 0 ? (int)(getServiceIdByPersonnel($idPersonnel) ?? 0) : 0;

    $error = '';
    $availableLits = [];

    if ($idService <= 0) {
        $error = "Service introuvable pour cet utilisateur.";
    } else {
        /*
        ----------------------------------------------------------------------
        | On affiche uniquement les lits disponibles du service concerné
        ----------------------------------------------------------------------
        */
        $availableLits = getAvailableLits($idService);
    }

    require __DIR__ . '/../views/lits/reserver.php';
}

/**
 * Traiter la réservation d'un lit.
 *
 * Règles métier :
 * - on re-vérifie que le dossier ne possède pas déjà un lit ;
 * - on calcule la date de fin selon le délai de prise en charge ;
 * - on enregistre l'infirmier ayant effectué la réservation ;
 * - puis on redirige vers le détail du dossier.
 */
function lit_reserver(): void
{
    /*
    |--------------------------------------------------------------------------
    | Vérification : utilisateur connecté
    |--------------------------------------------------------------------------
    */
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification : rôle autorisé
    |--------------------------------------------------------------------------
    */
    requireRole('INFIRMIER_ACCUEIL');

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres envoyés par le formulaire
    |--------------------------------------------------------------------------
    */
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $idLit = (int)($_POST['idLit'] ?? 0);

    if ($idDossier <= 0 || $idLit <= 0) {
        echo "Paramètres invalides.";
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Date de début de réservation
    |--------------------------------------------------------------------------
    */
    $dateDebut = $_POST['dateDebut'] ?? date('Y-m-d H:i:s');

    /*
    |--------------------------------------------------------------------------
    | Calcul de la date de fin selon le délai de prise en charge
    |--------------------------------------------------------------------------
    */
    $dossier = getDossierById($idDossier);
    $delai = $dossier['delaiPriseCharge'] ?? '0';

    switch ((string)$delai) {
        case '0':
            $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut . ' +1 minute'));
            break;

        case '10':
            $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut . ' +10 minutes'));
            break;

        case '30':
            $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut . ' +30 minutes'));
            break;

        case 'NonImmediat':
            $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut . ' +2 hours'));
            break;

        default:
            $dateFin = $dateDebut;
    }

    /*
    |--------------------------------------------------------------------------
    | Double sécurité : le dossier ne doit pas déjà avoir un lit
    |--------------------------------------------------------------------------
    */
    $litDeja = getLitForDossier($idDossier);

    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Identifier l'infirmier d'accueil lié au personnel connecté
    |--------------------------------------------------------------------------
    */
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idInfirmier = getInfirmierIdByPersonnel($idPersonnel);

    if (!$idInfirmier) {
        $idInfirmier = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Tentative de réservation
    |--------------------------------------------------------------------------
    */
    try {
        reserveLitForDossier(
            $idLit,
            $idDossier,
            $idInfirmier,
            $dateDebut,
            $dateFin
        );

        $_SESSION['success'] = "Lit réservé avec succès.";

        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    } catch (Throwable $e) {
        /*
        ----------------------------------------------------------------------
        | En cas d'erreur, on recharge le formulaire avec les lits du service
        ----------------------------------------------------------------------
        */
        $error = $e->getMessage();

        $idService = $idPersonnel > 0 ? (int)(getServiceIdByPersonnel($idPersonnel) ?? 0) : 0;
        $availableLits = $idService > 0
            ? getAvailableLits($idService)
            : [];

        require __DIR__ . '/../views/lits/reserver.php';
    }
}


/* ======================================================================
   ACTIONS INFIRMIER
   ====================================================================== */

/**
 * Liste des lits pour infirmier.
 *
 * L'infirmier voit uniquement les lits du service Urgences.
 */
function lits_list_infirmier(): void
{
    requireRole('INFIRMIER');

    $idServiceUrgences = (int)(getServiceIdByName('Urgences') ?? 0);

    $lits = ($idServiceUrgences > 0)
        ? getLitsByService($idServiceUrgences)
        : [];

    require __DIR__ . '/../views/lits/liste_infirmier.php';
}

/**
 * Changer l'état d'un lit côté infirmier.
 *
 * Transitions autorisées :
 * - reserve   -> occupe
 * - occupe    -> disponible
 * - tout état -> en_panne
 */
function lit_changer_etat_infirmier(): void
{
    requireRole('INFIRMIER');

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres depuis POST ou GET
    |--------------------------------------------------------------------------
    */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idLit = (int)($_POST['idLit'] ?? 0);
        $etat = trim((string)($_POST['etat'] ?? ''));
    } else {
        $idLit = (int)($_GET['idLit'] ?? 0);
        $etat = trim((string)($_GET['etat'] ?? ''));
    }

    if ($idLit <= 0 || $etat === '') {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=lits_list_infirmier');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération du lit
    |--------------------------------------------------------------------------
    */
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
    | Transitions autorisées pour l'infirmier
    |--------------------------------------------------------------------------
    | - reserve -> occupe
    | - occupe  -> disponible
    | - tout état -> en_panne
    |
    | L'infirmier peut signaler une panne lorsqu'il constate un problème
    | pendant l'utilisation ou la préparation du lit.
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Refus si la transition n'est pas autorisée
    |--------------------------------------------------------------------------
    */
    if (!$allowed) {
        $_SESSION['flash_error'] = "Transition d’état non autorisée.";
        header('Location: index.php?action=lits_list_infirmier');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Mise à jour de l'état
    |--------------------------------------------------------------------------
    */
    $ok = lit_update_etat($idLit, $etat);

    if ($ok) {
        /*
        ----------------------------------------------------------------------
        | Si le lit est déclaré en panne, créer une alerte technique
        ----------------------------------------------------------------------
        */
        if ($etat === 'en_panne') {
            require_once APP_PATH . '/models/AlerteModel.php';

            alerte_create(
                'panne_Lit',
                "Lit {$lit['numeroLit']} déclaré en panne.",
                "index.php?action=lit_detail_technicien&idLit={$idLit}"
            );
        }

        $_SESSION['flash_success'] = "État du lit mis à jour.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
    }

    header('Location: index.php?action=lits_list_infirmier');
    exit;
}


/* ======================================================================
   ACTIONS TECHNICIEN
   ====================================================================== */

/**
 * Afficher la liste des lits côté technicien.
 */
function lits_liste_technicien(): void
{
    requireRole('TECHNICIEN');

    $lits = lits_get_all_for_technicien();

    require APP_PATH . '/views/lits/liste_technicien.php';
}

/**
 * Afficher le détail d'un lit côté technicien.
 */
function lit_detail_technicien(): void
{
    requireRole('TECHNICIEN');

    $idLit = (int)($_GET['idLit'] ?? ($_GET['id'] ?? 0));

    if ($idLit <= 0) {
        $_SESSION['flash_error'] = "ID lit invalide.";
        header('Location: index.php?action=lits_technicien');
        exit;
    }

    $lit = lit_get_detail_for_technicien($idLit);

    if (!$lit) {
        $_SESSION['flash_error'] = "Lit introuvable.";
        header('Location: index.php?action=lits_technicien');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération de la dernière maintenance pour la vue détail
    |--------------------------------------------------------------------------
    */
    $maintenance = lit_get_last_maintenance($idLit);

    require APP_PATH . '/views/lits/detail_technicien.php';
}

/**
 * Changer l'état d'un lit côté technicien.
 *
 * Transitions autorisées :
 * - en_panne    -> maintenance
 * - maintenance -> disponible ou HS
 */
function lit_changer_etat(): void
{
    requireRole('TECHNICIEN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération des données du formulaire
    |--------------------------------------------------------------------------
    */
    $idLit = (int)($_POST['idLit'] ?? 0);
    $etat = trim((string)($_POST['etat'] ?? ''));
    $probleme = trim((string)($_POST['probleme'] ?? 'Diagnostic / intervention technique'));

    $redirect = 'index.php?action=lit_detail_technicien&idLit=' . $idLit;

    if ($idLit <= 0 || $etat === '') {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=lits_technicien');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération du lit actuel
    |--------------------------------------------------------------------------
    */
    $lit = lit_get_by_id($idLit);

    if (!$lit) {
        $_SESSION['flash_error'] = "Lit introuvable.";
        header('Location: index.php?action=lits_technicien');
        exit;
    }

    $etatActuel = (string)($lit['etatLit'] ?? '');
    $allowed = false;

    /*
    |--------------------------------------------------------------------------
    | Vérification des transitions autorisées
    |--------------------------------------------------------------------------
    */
    if ($etatActuel === 'en_panne' && $etat === 'maintenance') {
        $allowed = true;
    }

    if ($etatActuel === 'maintenance' && in_array($etat, ['disponible', 'HS'], true)) {
        $allowed = true;
    }

    if (!$allowed) {
        $_SESSION['flash_error'] = "Transition d’état non autorisée pour le technicien.";
        header('Location: ' . $redirect);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Identification du technicien connecté
    |--------------------------------------------------------------------------
    */
    $idPersonnel = (int)($_SESSION['user']['idPersonnel'] ?? 0);
    $idTechnicien = getTechnicienIdByPersonnel($idPersonnel);

    if ($idTechnicien <= 0) {
        $_SESSION['flash_error'] = "Technicien introuvable.";
        header('Location: ' . $redirect);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction pour garantir la cohérence des mises à jour
    |--------------------------------------------------------------------------
    */
    $pdo = db();
    $pdo->beginTransaction();

    try {
        /*
        ----------------------------------------------------------------------
        | Si le lit passe en maintenance, ouvrir une maintenance
        ----------------------------------------------------------------------
        */
        if ($etat === 'maintenance') {
            maintenance_lit_open($idLit, $idTechnicien, $probleme);
        }

        /*
        ----------------------------------------------------------------------
        | Si la réparation se termine, fermer la maintenance en cours
        ----------------------------------------------------------------------
        */
        if (in_array($etat, ['disponible', 'HS'], true)) {
            maintenance_lit_close_open($idLit);
        }

        /*
        ----------------------------------------------------------------------
        | Mettre à jour l'état principal du lit
        ----------------------------------------------------------------------
        */
        lit_update_etat($idLit, $etat);

        /*
        ----------------------------------------------------------------------
        | Si le lit devient HS, créer une alerte dédiée
        ----------------------------------------------------------------------
        */
        if ($etat === 'HS') {
            require_once APP_PATH . '/models/AlerteModel.php';

            alerte_create(
                'panne_Lit',
                "Lit {$lit['numeroLit']} déclaré HS par le technicien.",
                "index.php?action=lit_detail_technicien&idLit={$idLit}"
            );
        }

        $pdo->commit();

        $_SESSION['flash_success'] = "État du lit mis à jour.";
        header('Location: ' . $redirect);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : " . $e->getMessage();
        header('Location: ' . $redirect);
        exit;
    }
}
