<?php
declare(strict_types=1);

/*
==================================================
 CONTROLLER : TransfertController
==================================================
 Rôle :
 - Médecin : demander un transfert
 - Directeur : valider / refuser
 - Historique : consulter les transferts d'un patient
==================================================
*/

require_once APP_PATH . '/models/TransfertModel.php';
require_once APP_PATH . '/models/AlerteModel.php';

/**
 * Formulaire (Médecin) : demander un transfert
 */
function transfert_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    if ($idDossier <= 0) {
        http_response_code(400);
        exit('ID dossier invalide.');
    }

    // Charger le dossier pour récupérer le patient
    require_once APP_PATH . '/models/DossierModel.php';

    $dossier = getDossierById($idDossier);

    if (!$dossier) {
        http_response_code(404);
        exit('Dossier introuvable.');
    }

    $idPatient = (int)($dossier['idPatient'] ?? 0);

    if ($idPatient <= 0) {
        http_response_code(400);
        exit('Patient introuvable dans le dossier.');
    }

    // Historique des transferts du patient
    $historique = transferts_get_by_patient($idPatient);
    $hopitaux   = hopitaux_get_all();

    require APP_PATH . '/views/transferts/form.php';
}

/**
 * Action (Médecin) : créer la demande
 */
function transfert_create_action(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $hopitalDestinataire = trim((string)($_POST['hopitalDestinataire'] ?? ''));
    $serviceDestinataire = trim((string)($_POST['serviceDestinataire'] ?? ''));

    if ($idDossier <= 0 || $hopitalDestinataire === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=transfert_form&idDossier=' . $idDossier);
        exit;
    }

    // Charger le dossier pour trouver le patient
    require_once APP_PATH . '/models/DossierModel.php';
    $dossier = getDossierById($idDossier);

    if (!$dossier) {
        $_SESSION['flash_error'] = "Dossier introuvable.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $idPatient = (int)($dossier['idPatient'] ?? 0);

    if ($idPatient <= 0) {
        $_SESSION['flash_error'] = "Patient introuvable.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    // Hôpital source : souvent depuis la session
    $user = $_SESSION['user'] ?? [];
    $idHopitalSource = (int)($user['idHopital'] ?? 0);

    // Valeur par défaut si absente
    if ($idHopitalSource <= 0) {
        $idHopitalSource = 1;
    }

    // Création de la demande de transfert dans l'historique
    $okTransfert = transfert_create_patient(
        $idPatient,
        $idHopitalSource,
        $hopitalDestinataire,
        ($serviceDestinataire !== '' ? $serviceDestinataire : null)
    );

    // Création d'une alerte pour informer le directeur
    $description = "Demande de transfert envoyée pour le patient ID "
        . $idPatient
        . " vers "
        . $hopitalDestinataire
        . ($serviceDestinataire !== '' ? " / service : " . $serviceDestinataire : "")
        . ".";

    $okAlerte = alerte_create('demande_transfert', $description, null);

    // Message final
    if ($okTransfert && $okAlerte) {
        $_SESSION['flash_success'] = "Demande de transfert bien envoyée au directeur.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur : impossible d'enregistrer la demande de transfert.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}

/**
 * Directeur : liste des demandes
 */
function transferts_traitement_directeur(): void
{
    requireRole('DIRECTEUR');

    $transferts = transferts_get_pending();

    require APP_PATH . '/views/transferts/traitement_directeur.php';
}

/**
 * Directeur : valider ou refuser
 */
function transfert_update_statut_action(): void
{
    requireRole('DIRECTEUR');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idTransfer = (int)($_POST['idTransfer'] ?? 0);
    $statut     = trim((string)($_POST['statut'] ?? ''));

    if ($idTransfer <= 0 || ($statut !== 'accepte' && $statut !== 'refuse')) {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=transferts_traitement_directeur');
        exit;
    }

    $ok = transfert_update_statut($idTransfer, $statut);

    if ($ok) {
        $_SESSION['flash_success'] = "Statut mis à jour : $statut";
    } else {
        $_SESSION['flash_error'] = "Erreur : mise à jour impossible.";
    }

    header('Location: index.php?action=transferts_traitement_directeur');
    exit;
}