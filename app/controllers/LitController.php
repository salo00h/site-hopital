<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/LitModel.php';
require_once __DIR__ . '/../models/DossierModel.php';

function lits_dashboard(): void
{
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    $idPersonnel = (int)$user['idPersonnel'];
    $idService = getServiceIdByPersonnel($idPersonnel);

    $error = '';
    $stats = [];
    $lits  = [];

    $nbDisponible = 0;
    $nbOccupe = 0;
    $nbReserve = 0;
    $nbHs = 0;
    $totalLits = 0;
    $tauxOccupation = 0;

    $alertLevel = '';
    $alertMessage = '';

    if (!$idService) {
        $error = "Service introuvable pour cet utilisateur.";
        require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
        return;
    }

    $stats = getLitStatsByService($idService);
    $lits  = getLitsByService($idService);

    $map = [];
    foreach ($stats as $s) {
        $etat = (string)$s['etatLit'];
        $map[$etat] = (int)$s['nb'];
    }

    $nbDisponible = $map['disponible'] ?? 0;
    $nbOccupe     = $map['occupe'] ?? 0;
    $nbReserve    = $map['reserve'] ?? 0;

    $nbHs = 0;
    $nbHs += $map['hs'] ?? 0;
    $nbHs += $map['HS'] ?? 0;
    $nbHs += $map['en_panne'] ?? 0;
    $nbHs += $map['maintenance'] ?? 0;

    $totalLits = $nbDisponible + $nbOccupe + $nbReserve + $nbHs;

    if ($totalLits > 0) {
        $tauxOccupation = (int)round((($nbOccupe + $nbReserve) / $totalLits) * 100);
    }

    if ($nbDisponible === 0) {
        $alertLevel = 'danger';
        $alertMessage = "Aucun lit disponible : envisager l'attente ou une demande de transfert.";
    } elseif ($nbDisponible <= 2) {
        $alertLevel = 'warning';
        $alertMessage = "Peu de lits disponibles : prioriser selon le niveau de gravité (triage).";
    }

    require __DIR__ . '/../views/lits/dashboard_infirmier_accueil.php';
}

function lit_reserver_form(): void
{
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        echo "idDossier manquant.";
        exit;
    }

    // Empêcher qu'un dossier réserve plusieurs lits
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    $idPersonnel = (int)$user['idPersonnel'];
    $idService = getServiceIdByPersonnel($idPersonnel);

    $error = '';
    $availableLits = [];

    if (!$idService) {
        $error = "Service introuvable.";
    } else {
        $availableLits = getAvailableLits($idService);
    }

    require __DIR__ . '/../views/lits/reserver.php';
}

function lit_reserver(): void
{
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $idLit     = (int)($_POST['idLit'] ?? 0);

    $dateDebut = $_POST['dateDebut'] ?? date('Y-m-d H:i:s');
    $dateFin   = $_POST['dateFin'] ?? date('Y-m-d H:i:s', time() + 2 * 3600);

    if ($idDossier <= 0 || $idLit <= 0) {
        echo "Paramètres invalides.";
        exit;
    }

    // Double sécurité
    $litDeja = getLitForDossier($idDossier);
    if ($litDeja) {
        $_SESSION['success'] = "Ce dossier a déjà le lit n°" . $litDeja['numeroLit'] . ".";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    $idPersonnel = (int)$user['idPersonnel'];
    $idInfirmier = getInfirmierIdByPersonnel($idPersonnel);

    if (!$idInfirmier) {
        echo "Infirmier introuvable pour cet utilisateur.";
        exit;
    }

    try {
        reserveLitForDossier($idLit, $idDossier, $idInfirmier, $dateDebut, $dateFin);
        $_SESSION['success'] = "Lit réservé avec succès.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $idService = getServiceIdByPersonnel($idPersonnel);
        $availableLits = $idService ? getAvailableLits($idService) : [];
        require __DIR__ . '/../views/lits/reserver.php';
    }
}