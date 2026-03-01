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

require_once __DIR__ . '/../models/LitModel.php';
require_once __DIR__ . '/../models/DossierModel.php';

/**
 * Tableau de bord des lits (infirmier accueil)
 * - stats par état (disponible / occupé / réservé / HS)
 * - calcul du taux d'occupation
 * - message d'alerte si peu ou pas de lits disponibles
 */
function lits_dashboard(): void
{
    // 1) Sécurité : utilisateur connecté
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    // 2) Trouver le service de l'utilisateur
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService   = getServiceIdByPersonnel($idPersonnel);

    // 3) Variables pour la view
    $error = '';
    $stats = [];
    $lits  = [];

    $nbDisponible = 0;
    $nbOccupe     = 0;
    $nbReserve    = 0;
    $nbHs         = 0;
    $totalLits    = 0;
    $tauxOccupation = 0;

    $alertLevel   = '';
    $alertMessage = '';

    // Si on ne trouve pas le service, on affiche la page avec une erreur
    if (!$idService) {
        $error = "Service introuvable pour cet utilisateur.";
        require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
        return;
    }

    // 4) Récupération des données (Model)
    $stats = getLitStatsByService($idService);
    $lits  = getLitsByService($idService);

    // 5) Transformer les stats en tableau simple : $map['etat'] = nb
    $map = [];
    foreach ($stats as $s) {
        $etat = (string)($s['etatLit'] ?? '');
        $map[$etat] = (int)($s['nb'] ?? 0);
    }

    // États principaux
    $nbDisponible = $map['disponible'] ?? 0;
    $nbOccupe     = $map['occupe'] ?? 0;
    $nbReserve    = $map['reserve'] ?? 0;

    // HS : on accepte plusieurs libellés (selon les données existantes)
    $nbHs  = 0;
    $nbHs += $map['hs'] ?? 0;
    $nbHs += $map['HS'] ?? 0;
    $nbHs += $map['en_panne'] ?? 0;
    $nbHs += $map['maintenance'] ?? 0;

    // Total
    $totalLits = $nbDisponible + $nbOccupe + $nbReserve + $nbHs;

    // Taux d'occupation (occupé + réservé)
    if ($totalLits > 0) {
        $tauxOccupation = (int) round((($nbOccupe + $nbReserve) / $totalLits) * 100);
    }

    // 6) Message d'alerte simple (pour l'infirmier accueil)
    if ($nbDisponible === 0) {
        $alertLevel = 'danger';
        $alertMessage = "Aucun lit disponible : envisager l'attente ou une demande de transfert.";
    } elseif ($nbDisponible <= 2) {
        $alertLevel = 'warning';
        $alertMessage = "Peu de lits disponibles : prioriser selon le niveau de gravité (triage).";
    }

    // 7) Affichage
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

    // 3) Règle métier : un dossier ne peut pas réserver plusieurs lits
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // 4) Trouver le service de l'utilisateur
    $idPersonnel = (int)($user['idPersonnel'] ?? 0);
    $idService   = getServiceIdByPersonnel($idPersonnel);

    // 5) Données pour la view
    $error = '';
    $availableLits = [];

    if (!$idService) {
        $error = "Service introuvable.";
    } else {
        $availableLits = getAvailableLits($idService);
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

    if (!$idInfirmier) {
        echo "Infirmier introuvable pour cet utilisateur.";
        exit;
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