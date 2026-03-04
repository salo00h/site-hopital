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
    // =========================
    // 1) Sécurité : session
    // =========================
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        // Non connecté -> retour au login
        header('Location: index.php?action=login_form');
        exit;
    }

    // =========================
    // 2) Sécurité : rôle
    // =========================
    // Seul l'infirmier d'accueil peut voir ce dashboard
    requireRole('INFIRMIER_ACCUEIL');

    // =========================
    // 3) Déterminer le service
    // =========================
    // On récupère l'idService via l'idPersonnel de l'utilisateur connecté.
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService   = $idPersonnel > 0 ? (int)(getServiceIdByPersonnel($idPersonnel) ?? 0) : 0;

    // =========================
    // 4) Variables pour la vue
    // =========================
    $error = '';
    $stats = [];
    $lits  = [];

    $nbDisponible    = 0;
    $nbOccupe        = 0;
    $nbReserve       = 0;
    $nbHs            = 0;
    $totalLits       = 0;
    $tauxOccupation  = 0;

    $alertLevel      = '';
    $alertMessage    = '';

    // Si pas de service -> on affiche une erreur propre
    if ($idService <= 0) {
        $error = "Service introuvable pour cet utilisateur.";
        require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
        return;
    }

    // =========================
    // 5) Récupération des données (Model)
    // =========================
    $stats = getLitStatsByService($idService);
    $lits  = getLitsByService($idService);

    // =========================
    // 6) Mise en forme des stats
    // =========================
    // Transformer le résultat SQL en map : $map['etat'] = nb
    $map = [];
    foreach ($stats as $s) {
        $etat = (string)($s['etatLit'] ?? '');
        $map[$etat] = (int)($s['nb'] ?? 0);
    }

    // États principaux
    $nbDisponible = $map['disponible'] ?? 0;
    $nbOccupe     = $map['occupe'] ?? 0;
    $nbReserve    = $map['reserve'] ?? 0;

    // HS : accepter plusieurs libellés selon les données
    $nbHs  = 0;
    $nbHs += $map['hs'] ?? 0;
    $nbHs += $map['HS'] ?? 0;
    $nbHs += $map['en_panne'] ?? 0;
    $nbHs += $map['maintenance'] ?? 0;

    // Total lits
    $totalLits = $nbDisponible + $nbOccupe + $nbReserve + $nbHs;

    // =========================
    // 7) Calcul du taux d’occupation
    // =========================
    // On considère "occupé + réservé" comme non disponible
    if ($totalLits > 0) {
        $tauxOccupation = (int) round((($nbOccupe + $nbReserve) / $totalLits) * 100);
    }

    // =========================
    // 8) Message d’alerte simple
    // =========================
    if ($nbDisponible === 0) {
        $alertLevel = 'danger';
        $alertMessage = "Aucun lit disponible : envisager l'attente ou une demande de transfert.";
    } elseif ($nbDisponible <= 2) {
        $alertLevel = 'warning';
        $alertMessage = "Peu de lits disponibles : prioriser selon le niveau de gravité (triage).";
    }

    // =========================
    // 9) Affichage de la vue
    // =========================
    require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
}

/**
 * Formulaire de réservation d'un lit
 * - lit disponible uniquement dans le service de l'infirmier
 * - on empêche un dossier de réserver plusieurs lits
 */
function lit_reserver_form(): void
{
    // 1) Sécurité : utilisateur connecté
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // 2) Lire idDossier depuis l'URL
    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        echo "idDossier manquant.";
        exit;
    }

    // 3) Vérifier si le dossier a déjà un lit
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] =
            "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // 4) Récupérer le dossier
    $dossier = getDossierById($idDossier);
    if (!$dossier) {
        echo "Dossier introuvable.";
        exit;
    }

    // 5) On prend l'hôpital du dossier
    $idHopital = (int)($dossier['idHopital'] ?? 0);

    $error = '';
    $availableLits = [];

    if ($idHopital <= 0) {
        $error = "Hôpital introuvable pour ce dossier.";
    } else {
        // On récupère les lits disponibles via l'hôpital
        $availableLits = getAvailableLitsByHopital($idHopital);
    }

    // 6) Affichage
    require __DIR__ . '/../views/lits/reserver.php';
}

/**
 * Traitement de la réservation (POST)
 * - double sécurité : re-vérifier que le dossier n'a pas déjà un lit
 * - enregistrer la réservation et rediriger vers détail dossier
 */
function lit_reserver(): void
{
    // 1) Sécurité : utilisateur connecté
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // 2) Lire les paramètres POST
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $idLit     = (int)($_POST['idLit'] ?? 0);

    // Dates (valeurs par défaut si non fournies)
    $dateDebut = $_POST['dateDebut'] ?? date('Y-m-d H:i:s');
    $dateFin   = $_POST['dateFin'] ?? date('Y-m-d H:i:s', time() + 2 * 3600);

    if ($idDossier <= 0 || $idLit <= 0) {
        echo "Paramètres invalides.";
        exit;
    }

    // 3) Double sécurité : un dossier ne peut pas réserver plusieurs lits
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // 4) Trouver l'infirmier (à partir du personnel)
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idInfirmier = getInfirmierIdByPersonnel($idPersonnel);

    // Si ce n'est pas un infirmier (ex: médecin),
    // on met NULL comme idInfirmier
    if (!$idInfirmier) {
     $idInfirmier = null;
    }

    // 5) Réserver (Model) + gestion d'erreur simple
    try {
        reserveLitForDossier($idLit, $idDossier, $idInfirmier, $dateDebut, $dateFin);
        $_SESSION['success'] = "Lit réservé avec succès.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    } catch (Throwable $e) {
        // On ré-affiche le formulaire avec un message d'erreur
        $error = $e->getMessage();

        // Pour ré-afficher la liste des lits disponibles
        $idService = getServiceIdByPersonnel($idPersonnel);
        $availableLits = $idService ? getAvailableLits($idService) : [];

        require __DIR__ . '/../views/lits/reserver.php';
    }
}