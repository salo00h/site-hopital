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
 * Afficher la liste des équipements côté médecin.
 * Même vue que l'infirmier : views/equipements/liste.php
 */
function equipements_list_medecin(): void
{
    requireRole('MEDECIN');

    $equipements = equipements_get_all_with_patient();

    /*
    |----------------------------------------------------------------------
    | Tri des équipements par priorité d'affichage
    |----------------------------------------------------------------------
    | Ordre :
    | 1. reserve
    | 2. occupe
    | 3. disponible
    | 4. en_panne
    | 5. maintenance
    | 6. HS
    */
    usort($equipements, function ($a, $b) {
        $order = [
            'reserve'     => 1,
            'occupe'      => 2,
            'disponible'  => 3,
            'en_panne'    => 4,
            'maintenance' => 5,
            'HS'          => 6,
        ];

        $etatA = (string)($a['etatEquipement'] ?? '');
        $etatB = (string)($b['etatEquipement'] ?? '');

        return ($order[$etatA] ?? 99) <=> ($order[$etatB] ?? 99);
    });

    require APP_PATH . '/views/equipements/liste.php';
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
 * - Puis on met l’équipement en état "reserve"
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

        equipement_set_reserve($idEquipement);

        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";

        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible. " . $e->getMessage();

        header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
        exit;
    }
}


/**
 * Signale une panne sur un équipement côté médecin.
 */
function equipement_signaler_panne(): void
{
    requireRole('MEDECIN');

    $idEquipement = (int)($_GET['idEquipement'] ?? ($_GET['id'] ?? 0));

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "Paramètre idEquipement invalide.";
        header('Location: index.php?action=equipements_list_medecin');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_list_medecin');
        exit;
    }

    $etat = $equipement['etatEquipement'] ?? '';

    if (in_array($etat, ['en_panne', 'maintenance', 'HS'], true)) {
        $_SESSION['flash_error'] = "L'équipement est déjà indisponible.";
    } else {
        equipement_set_panne($idEquipement);
        
        $_SESSION['flash_success'] = "Panne signalée. L'équipement est maintenant en panne.";
    }

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    $url = 'index.php?action=equipements_list_medecin';
    if ($idDossier > 0) {
        $url .= '&idDossier=' . $idDossier;
    }

    header('Location: ' . $url);
    exit;
}


/**
 * Liste des équipements pour infirmier.
 * Même vue que le médecin : views/equipements/liste.php
 */
function equipements_list_infirmier(): void
{
    requireRole('INFIRMIER');

    $equipements = equipements_get_all_with_patient();

    /*
    |----------------------------------------------------------------------
    | Tri des équipements par priorité d'affichage
    |----------------------------------------------------------------------
    | Ordre :
    | 1. reserve
    | 2. occupe
    | 3. disponible
    | 4. en_panne
    | 5. maintenance
    | 6. HS
    */
    usort($equipements, function ($a, $b) {
        $order = [
            'reserve'     => 1,
            'occupe'      => 2,
            'disponible'  => 3,
            'en_panne'    => 4,
            'maintenance' => 5,
            'HS'          => 6,
        ];

        $etatA = (string)($a['etatEquipement'] ?? '');
        $etatB = (string)($b['etatEquipement'] ?? '');

        return ($order[$etatA] ?? 99) <=> ($order[$etatB] ?? 99);
    });

    require APP_PATH . '/views/equipements/liste.php';
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

        equipement_set_reserve($idEquipement);

        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible. " . $e->getMessage();
        header('Location: index.php?action=equipements_list_infirmier&idDossier=' . $idDossier);
        exit;
    }
}


/**
 * Signale une panne côté infirmier.
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

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_list_infirmier');
        exit;
    }

    $etat = $equipement['etatEquipement'] ?? '';

    // Vérifier si déjà indisponible
    if (in_array($etat, ['en_panne', 'maintenance', 'HS'], true)) {
        $_SESSION['flash_error'] = "L'équipement est déjà indisponible.";
    } else {
        // Passer en panne
        equipement_set_panne($idEquipement);

        // Supprimer le lien avec le dossier
        

        $_SESSION['flash_success'] = "Panne signalée.";
    }

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    $url = "index.php?action=equipements_list_infirmier";
    if ($idDossier > 0) {
        $url .= "&idDossier=" . $idDossier;
    }

    header("Location: $url");
    exit;
}


/**
 * Mettre un équipement réservé en cours d'utilisation côté infirmier.
 */
function equipement_utiliser(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier    = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier    = (int)($_GET['idDossier'] ?? 0);
    }

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement || ($equipement['etatEquipement'] ?? '') !== 'reserve') {
        $_SESSION['flash_error'] = "Seul un équipement réservé peut être utilisé.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    equipement_set_occupe($idEquipement);

    $_SESSION['flash_success'] = "Équipement maintenant en cours d'utilisation.";
    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}


/**
 * Libère un équipement côté infirmier.
 *
 * Cette fonction permet :
 * - de vérifier que l'utilisateur est bien un infirmier
 * - de récupérer les paramètres (POST ou GET)
 * - de vérifier que l'équipement existe et qu'il est bien "occupé"
 * - de remettre l'équipement en état "disponible"
 * - puis de rediriger vers la page du dossier avec un message de succès
 *
 * Remarque :
 * La redirection doit être faite avec header() avant tout affichage :contentReference[oaicite:0]{index=0}
 */
function equipement_liberer(): void
{
    // Vérifier que l'utilisateur est infirmier
    requireRole('INFIRMIER');

    // Récupération des données (POST ou GET)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier    = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier    = (int)($_GET['idDossier'] ?? 0);
    }

    // Vérification ID équipement
    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    // Récupération équipement
    $equipement = equipement_get_by_id($idEquipement);

    // Vérifier que l'équipement est bien occupé
    if (!$equipement || ($equipement['etatEquipement'] ?? '') !== 'occupe') {
        $_SESSION['flash_error'] = "Seul un équipement occupé peut être libéré.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    //  LA LIGNE IMPORTANTE (correction)
    equipement_update_etat($idEquipement, 'disponible');

    // Message succès + redirection
    $_SESSION['flash_success'] = "Équipement libéré.";
    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}

/**
 * Mettre un équipement réservé en cours d'utilisation côté médecin.
 */
function equipement_utiliser_medecin(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier    = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier    = (int)($_GET['idDossier'] ?? 0);
    }

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement || ($equipement['etatEquipement'] ?? '') !== 'reserve') {
        $_SESSION['flash_error'] = "Seul un équipement réservé peut être utilisé.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    equipement_set_occupe($idEquipement);

    $_SESSION['flash_success'] = "Équipement maintenant en cours d'utilisation.";
    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}


/**
 * Libère un équipement (médecin) : passe l'état à "disponible".
 */
function equipement_liberer_medecin(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier    = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier    = (int)($_GET['idDossier'] ?? 0);
    }

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement || ($equipement['etatEquipement'] ?? '') !== 'occupe') {
        $_SESSION['flash_error'] = "Seul un équipement occupé peut être libéré.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;
    }

    equipement_update_etat($idEquipement, 'disponible');

    $_SESSION['flash_success'] = "Équipement libéré.";
    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}