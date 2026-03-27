<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONTROLLER : TransfertController
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - Médecin : demander un transfert ;
| - Directeur : valider ou refuser une demande ;
| - Historique : consulter les transferts enregistrés.
|
| Organisation du fichier :
| 1. Actions médecin
| 2. Actions directeur
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/models/TransfertModel.php';
require_once APP_PATH . '/models/AlerteModel.php';


/* ======================================================================
   ACTIONS MÉDECIN
   ====================================================================== */

/**
 * Afficher le formulaire de demande de transfert.
 */
function transfert_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    if ($idDossier <= 0) {
        http_response_code(400);
        exit('ID dossier invalide.');
    }

    /*
    |--------------------------------------------------------------------------
    | Chargement du dossier pour retrouver le patient lié
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Données utiles pour le formulaire
    |--------------------------------------------------------------------------
    | - historique complet des transferts du patient ;
    | - liste des hôpitaux possibles.
    |--------------------------------------------------------------------------
    */
    $historique = transferts_get_by_patient($idPatient);
    $hopitaux = hopitaux_get_all();

    require APP_PATH . '/views/transferts/form.php';
}

/**
 * Traiter la création d'une demande de transfert.
 */
function transfert_create_action(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres du formulaire
    |--------------------------------------------------------------------------
    */
    $typeTransfert = trim((string)($_POST['typeTransfert'] ?? ''));
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $hopitalDestinataire = trim((string)($_POST['hopitalDestinataire'] ?? ''));
    $serviceDestinataire = trim((string)($_POST['serviceDestinataire'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Vérification des champs obligatoires
    |--------------------------------------------------------------------------
    */
    if ($idDossier <= 0 || $typeTransfert === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=transfert_form&idDossier=' . $idDossier);
        exit;
    }

    if ($typeTransfert === 'hopital' && $hopitalDestinataire === '') {
        $_SESSION['flash_error'] = "Veuillez choisir l'hôpital destinataire.";
        header('Location: index.php?action=transfert_form&idDossier=' . $idDossier);
        exit;
    }

    if ($typeTransfert === 'service' && $serviceDestinataire === '') {
        $_SESSION['flash_error'] = "Veuillez saisir le service destinataire.";
        header('Location: index.php?action=transfert_form&idDossier=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération du dossier et du patient concerné
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Hôpital source lié à l'utilisateur connecté
    |--------------------------------------------------------------------------
    */
    $user = $_SESSION['user'] ?? [];
    $idHopitalSource = (int)($user['idHopital'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Valeur de secours si l'hôpital source n'est pas défini
    |--------------------------------------------------------------------------
    */
    if ($idHopitalSource <= 0) {
        $idHopitalSource = 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Distinction transfert interne / externe
    |--------------------------------------------------------------------------
    | Avant :
    | le dossier passait toujours au statut général "transfert".
    |
    | Problème :
    | l'application ne montrait pas clairement s'il s'agissait
    | d'un transfert interne ou externe.
    |
    | Maintenant :
    | - transfert interne  -> statut dossier = transfert_interne
    | - transfert externe  -> statut dossier = transfert_externe
    |
    | En plus, la table transfert_patient enregistre :
    | - le type de transfert ;
    | - la destination ;
    | - la validation du directeur pour l'externe.
    |--------------------------------------------------------------------------
    */
    if ($typeTransfert === 'service') {
        $statutTransfer = 'interne';
        $typeTransfer = 'interne';
        $validationDirecteur = null;
        $statutDossier = 'transfert_interne';

        /*
        ----------------------------------------------------------------------
        | Pour un transfert interne, on garde une indication simple
        | dans la colonne hôpital afin que l'affichage reste clair.
        ----------------------------------------------------------------------
        */
        $hopitalDestinataire = 'Interne';
    } else {
        $statutTransfer = 'demande';
        $typeTransfer = 'externe';
        $validationDirecteur = 'en_attente';
        $statutDossier = 'transfert_externe';
    }

    /*
    |--------------------------------------------------------------------------
    | Création du transfert en base
    |--------------------------------------------------------------------------
    */
    $okTransfert = transfert_create_patient(
        $idPatient,
        $idHopitalSource,
        $hopitalDestinataire,
        ($serviceDestinataire !== '' ? $serviceDestinataire : null),
        $statutTransfer,
        $typeTransfer,
        $validationDirecteur
    );

    /*
    |--------------------------------------------------------------------------
    | Mise à jour du statut du dossier si le transfert est bien créé
    |--------------------------------------------------------------------------
    */
    if ($okTransfert) {
        dossier_update_statut($idDossier, $statutDossier);
    }

    /*
    |--------------------------------------------------------------------------
    | Construction du texte d'alerte selon le type de transfert
    |--------------------------------------------------------------------------
    */
    if ($typeTransfert === 'service') {
        $description = "Transfert interne du patient ID "
            . $idPatient
            . ($serviceDestinataire !== '' ? " vers le service : " . $serviceDestinataire : "")
            . ".";
    } else {
        $description = "Demande de transfert externe pour le patient ID "
            . $idPatient
            . " vers "
            . $hopitalDestinataire
            . ($serviceDestinataire !== '' ? " / service : " . $serviceDestinataire : "")
            . ".";
    }

    /*
    |--------------------------------------------------------------------------
    | Création d'une alerte liée à la demande de transfert
    |--------------------------------------------------------------------------
    */
    $okAlerte = alerte_create('demande_transfert', $description, null);

    /*
    |--------------------------------------------------------------------------
    | Messages utilisateur
    |--------------------------------------------------------------------------
    */
    if ($okTransfert && $okAlerte) {
        if ($typeTransfert === 'service') {
            $_SESSION['flash_success'] = "Transfert interne enregistré avec succès.";
        } else {
            $_SESSION['flash_success'] = "Demande de transfert externe envoyée au directeur.";
        }

        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur : impossible d'enregistrer la demande de transfert.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}


/* ======================================================================
   ACTIONS DIRECTEUR
   ====================================================================== */

/**
 * Afficher la liste des demandes de transfert à traiter par le directeur.
 */
function transferts_traitement_directeur(): void
{
    requireRole('DIRECTEUR');

    $transferts = transferts_get_pending();

    require APP_PATH . '/views/transferts/traitement_directeur.php';
}

/**
 * Mettre à jour le statut d'un transfert côté directeur.
 *
 * Le directeur peut :
 * - accepter ;
 * - refuser.
 */
function transfert_update_statut_action(): void
{
    requireRole('DIRECTEUR');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres
    |--------------------------------------------------------------------------
    */
    $idTransfer = (int)($_POST['idTransfer'] ?? 0);
    $statut = trim((string)($_POST['statut'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Vérification des paramètres
    |--------------------------------------------------------------------------
    */
    if ($idTransfer <= 0 || ($statut !== 'accepte' && $statut !== 'refuse')) {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=transferts_traitement_directeur');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Mise à jour du statut dans le modèle
    |--------------------------------------------------------------------------
    */
    $ok = transfert_update_statut($idTransfer, $statut);

    if ($ok) {
        $_SESSION['flash_success'] = "Statut mis à jour : $statut";
    } else {
        $_SESSION['flash_error'] = "Erreur : mise à jour impossible.";
    }

    header('Location: index.php?action=transferts_traitement_directeur');
    exit;
}

/**
 * Afficher l'historique complet des transferts côté directeur.
 */
function transferts_historique(): void
{
    requireRole('DIRECTEUR');

    $transferts = transferts_get_recent(50);

    require APP_PATH . '/views/transferts/historique.php';
}
