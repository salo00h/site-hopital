<?php
declare(strict_types=1);

/*
==================================================
 CONTROLLER : EquipementController
==================================================
 Rôle :
 - Gérer les actions liées aux équipements
 - Appeler le Model
 - Charger les vues
==================================================
*/

require_once APP_PATH . '/models/EquipementModel.php';
require_once APP_PATH . '/includes/auth_guard.php';


/**
 * Afficher la liste des équipements (médecin).
 */
function equipements_list_medecin(): void
{
    requireRole('MEDECIN');

    $equipements = equipements_get_all();

    require APP_PATH . '/views/equipements/liste_medecin.php';
}


/**
 * Afficher le formulaire de réservation d’un équipement.
 * On garde aussi l'idDossier pour savoir pour quel patient
 * l’équipement sera réservé.
 */
function equipement_reserver_form(): void
{
    requireRole('MEDECIN');

    $idEquipement = (int)($_GET['idEquipement'] ?? 0);
    $idDossier    = (int)($_GET['idDossier'] ?? 0);

    if ($idEquipement <= 0) {
        http_response_code(400);
        exit('ID équipement invalide.');
    }

    if ($idDossier <= 0) {
        http_response_code(400);
        exit('ID dossier invalide.');
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        http_response_code(404);
        exit('Équipement introuvable.');
    }

    require APP_PATH . '/views/equipements/reserver_form.php';
}


/**
 * Traiter la réservation d’un équipement.
 *
 * Règles :
 * - L’équipement doit être disponible
 * - On enregistre la relation dossier ↔ équipement
 * - Puis on met l’équipement en état "occupe"
 *
 * Transaction :
 * INSERT + UPDATE doivent réussir ensemble.
 */
function equipement_reserver(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $idDossier    = (int)($_POST['idDossier'] ?? 0);

    if ($idEquipement <= 0 || $idDossier <= 0) {
        http_response_code(400);
        exit('Paramètres invalides.');
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        http_response_code(404);
        exit('Équipement introuvable.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $ok = gestion_equipement_add($idDossier, $idEquipement);

        if (!$ok) {
            $pdo->rollBack();

            $_SESSION['flash_error'] = "Impossible : équipement en panne ou non disponible.";

            header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
            exit;
        }

        equipement_set_occupe($idEquipement);

        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";

        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible.";

        header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
        exit;
    }
}


/**
 * Signaler une panne sur un équipement.
 * Compatible avec ?idEquipement= ou ?id=
 */
function equipement_signaler_panne(): void
{
    requireRole('MEDECIN');

    $idEquipement = (int)($_GET['idEquipement'] ?? ($_GET['id'] ?? 0));

    if ($idEquipement <= 0) {
        http_response_code(400);
        echo "Paramètre idEquipement invalide.";
        exit;
    }

    $ok = equipement_set_panne($idEquipement);

    if ($ok) {
        $_SESSION['flash_success'] = "Panne signalée. L'équipement est maintenant en panne.";
        $_SESSION['flash_error']   = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error']   = "Erreur : impossible de signaler la panne.";
    }

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    $url = "index.php?action=equipements_list_medecin";

    if ($idDossier > 0) {
        $url .= "&idDossier=" . $idDossier;
    }

    header("Location: $url");
    exit;
}


/**
 * Liste des équipements pour infirmier.
 */
function equipements_list_infirmier(): void
{
    requireRole('INFIRMIER');

    $equipements = equipements_get_all();

    require APP_PATH . '/views/equipements/liste_infirmier.php';
}


/**
 * Changer l’état d’un équipement pour infirmier.
 * Ici on garde simple :
 * - disponible -> occupe
 * - occupe -> disponible
 * - tout état sauf en_panne -> en_panne
 */
function equipement_changer_etat_infirmier(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $etat = trim((string)($_POST['etat'] ?? ''));
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $etat = trim((string)($_GET['etat'] ?? ''));
    }

    if ($idEquipement <= 0 || $etat === '') {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=equipements_list_infirmier');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_list_infirmier');
        exit;
    }

    $etatActuel = (string)($equipement['etatEquipement'] ?? '');
    $allowed = false;

    if ($etatActuel === 'disponible' && $etat === 'occupe') {
        $allowed = true;
    }

    if ($etatActuel === 'occupe' && $etat === 'disponible') {
        $allowed = true;
    }

    if ($etat !== 'en_panne' && !$allowed) {
        $_SESSION['flash_error'] = "Transition d’état non autorisée.";
        header('Location: index.php?action=equipements_list_infirmier');
        exit;
    }

    $ok = equipement_update_etat($idEquipement, $etat);

    if ($ok) {
        $_SESSION['flash_success'] = "État de l’équipement mis à jour avec succès.";
    } else {
        $_SESSION['flash_error'] = "Impossible de modifier l’état de l’équipement.";
    }

    header('Location: index.php?action=equipements_list_infirmier');
    exit;
}


/**
 * Afficher le formulaire de réservation d’un équipement pour infirmier.
 */
function equipement_reserver_form_infirmier(): void
{
    requireRole('INFIRMIER');

    $idEquipement = (int)($_GET['idEquipement'] ?? 0);
    $idDossier    = (int)($_GET['idDossier'] ?? 0);

    if ($idEquipement <= 0 || $idDossier <= 0) {
        http_response_code(400);
        exit('Paramètres invalides.');
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        http_response_code(404);
        exit('Équipement introuvable.');
    }

    require APP_PATH . '/views/equipements/reserver_form.php';
}


/**
 * Traiter la réservation d’un équipement pour infirmier.
 */
function equipement_reserver_infirmier(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $idDossier    = (int)($_POST['idDossier'] ?? 0);

    if ($idEquipement <= 0 || $idDossier <= 0) {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=equipements_list_infirmier&idDossier=' . $idDossier);
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_list_infirmier&idDossier=' . $idDossier);
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $ok = gestion_equipement_add($idDossier, $idEquipement);

        if (!$ok) {
            $pdo->rollBack();

            $_SESSION['flash_error'] = "Impossible : équipement en panne ou non disponible.";
            header('Location: index.php?action=equipements_list_infirmier&idDossier=' . $idDossier);
            exit;
        }

        equipement_set_occupe($idEquipement);

        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible.";
        header('Location: index.php?action=equipements_list_infirmier&idDossier=' . $idDossier);
        exit;
    }
}


/**
 * Signaler une panne sur un équipement pour infirmier.
 */
function equipement_signaler_panne_infirmier(): void
{
    requireRole('INFIRMIER');

    $idEquipement = (int)($_GET['idEquipement'] ?? ($_GET['id'] ?? 0));

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "Paramètre idEquipement invalide.";
        header('Location: index.php?action=equipements_list_infirmier');
        exit;
    }

    $ok = equipement_set_panne($idEquipement);

    if ($ok) {
        $_SESSION['flash_success'] = "Panne signalée. L'équipement est maintenant en panne.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur : impossible de signaler la panne.";
    }

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    $url = "index.php?action=equipements_list_infirmier";
    if ($idDossier > 0) {
        $url .= "&idDossier=" . $idDossier;
    }

    header("Location: $url");
    exit;
}