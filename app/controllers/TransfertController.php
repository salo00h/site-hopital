<?php
declare(strict_types=1);

/*
==================================================
 CONTROLLER : TransfertController
==================================================
 Rôle :
 - Médecin : demander un transfert
 - Directeur : valider / refuser
 - Historique : consulter transferts d'un patient
==================================================
*/

require_once APP_PATH . '/models/TransfertModel.php';

/**
 * Formulaire (Médecin) : demander transfert
 */
function transfert_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        http_response_code(400);
        exit('ID dossier invalide.');
    }

    // Charger le modèle dossier pour récupérer nom/prénom + idPatient
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

    // Historique transferts du patient
    $historique = transferts_get_by_patient($idPatient);
    $hopitaux = hopitaux_get_all();

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

    // Charger dossier pour trouver idPatient + hopital source
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

    // idHopital source: حسب مشروعك، غالباً من session user
    $user = $_SESSION['user'] ?? [];
    $idHopitalSource = (int)($user['idHopital'] ?? 0);

    // إذا ما عندكش idHopital فـ session، خليه 1 مؤقتاً (أفضل تجيبه من personnel/hopital)
    if ($idHopitalSource <= 0) {
        $idHopitalSource = 1;
    }

    $ok = transfert_create_patient(
        $idPatient,
        $idHopitalSource,
        $hopitalDestinataire,
        ($serviceDestinataire !== '' ? $serviceDestinataire : null)
    );

    if ($ok) {
        $_SESSION['flash_success'] = "Demande de transfert créée (statut : demande).";
    } else {
        $_SESSION['flash_error'] = "Erreur : impossible de créer la demande.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}

/**
 * Directeur : liste des demandes (traitement)
 */
function transferts_traitement_directeur(): void
{
    requireRole('DIRECTEUR');

    $transferts = transferts_get_pending();

    require APP_PATH . '/views/transferts/traitement_directeur.php';
}

/**
 * Directeur : action valider/refuser
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