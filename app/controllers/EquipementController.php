<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONTROLLER : EquipementController
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - gérer les actions liées aux équipements ;
| - appeler les fonctions du modèle ;
| - vérifier les droits d'accès ;
| - charger les vues adaptées ;
| - ne jamais contenir de logique SQL.
|
| Organisation du fichier :
| 1. Chargements communs
| 2. Actions médecin
| 3. Actions infirmier
| 4. Actions technicien
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/models/EquipementModel.php';
require_once APP_PATH . '/includes/auth_guard.php';


/* ======================================================================
   ACTIONS MEDECIN
   ====================================================================== */

/**
 * Afficher la liste des équipements côté médecin.
 * La vue utilisée est la même que pour l'infirmier.
 */
function equipements_list_medecin(): void
{
    requireRole('MEDECIN');

    $equipements = equipements_get_all_with_patient();

    /*
    |--------------------------------------------------------------------------
    | Tri des équipements par priorité d'affichage
    |--------------------------------------------------------------------------
    | Ordre retenu :
    | 1. reserve
    | 2. occupe
    | 3. disponible
    | 4. en_panne
    | 5. maintenance
    | 6. HS
    |--------------------------------------------------------------------------
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
 * Afficher le formulaire de réservation d'un équipement côté médecin.
 * On conserve également l'id du dossier afin de relier la réservation au patient.
 */
function equipement_reserver_form(): void
{
    requireRole('MEDECIN');

    $idEquipement = (int)($_GET['idEquipement'] ?? 0);
    $idDossier = (int)($_GET['idDossier'] ?? 0);

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
 * Traiter la réservation d'un équipement côté médecin.
 *
 * Règles métier :
 * - l'équipement doit être disponible ;
 * - la relation dossier ↔ équipement doit être enregistrée ;
 * - l'équipement passe ensuite à l'état "reserve".
 *
 * La transaction garantit que l'insertion et la mise à jour réussissent ensemble.
 */
function equipement_reserver(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);

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
        $idPersonnelAction = (int)($_SESSION['user']['idPersonnel'] ?? 0);

        $ok = gestion_equipement_add(
            $idDossier,
            $idEquipement,
            $idPersonnelAction,
            'MEDECIN'
        );

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
 * Signaler une panne côté médecin.
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
        equipement_update_etat($idEquipement, 'en_panne');

        require_once APP_PATH . '/models/AlerteModel.php';

        alerte_create(
            'panne_Equipement',
            "Équipement {$equipement['typeEquipement']} n°{$equipement['numeroEquipement']} déclaré en panne.",
            "index.php?action=equipement_detail_technicien&idEquipement={$idEquipement}"
        );

        $_SESSION['flash_success'] = "Panne signalée.";
    }

    header('Location: index.php?action=equipements_list_medecin');
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
        $idDossier = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier = (int)($_GET['idDossier'] ?? 0);
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
 * Libérer un équipement côté médecin.
 * L'équipement passe de "occupe" à "disponible".
 */
function equipement_liberer_medecin(): void
{
    requireRole('MEDECIN');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier = (int)($_GET['idDossier'] ?? 0);
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


/* ======================================================================
   ACTIONS INFIRMIER
   ====================================================================== */

/**
 * Liste des équipements côté infirmier.
 * La vue utilisée est la même que pour le médecin.
 */
function equipements_list_infirmier(): void
{
    requireRole('INFIRMIER');

    $equipements = equipements_get_all_with_patient();

    /*
    |--------------------------------------------------------------------------
    | Tri des équipements par priorité d'affichage
    |--------------------------------------------------------------------------
    | Ordre retenu :
    | 1. reserve
    | 2. occupe
    | 3. disponible
    | 4. en_panne
    | 5. maintenance
    | 6. HS
    |--------------------------------------------------------------------------
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
 * Afficher le formulaire de réservation d'un équipement côté infirmier.
 */
function equipement_reserver_form_infirmier(): void
{
    requireRole('INFIRMIER');

    $idEquipement = (int)($_GET['idEquipement'] ?? 0);
    $idDossier = (int)($_GET['idDossier'] ?? 0);

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
 * Traiter la réservation d'un équipement côté infirmier.
 */
function equipement_reserver_infirmier(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);

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
        $idPersonnelAction = (int)($_SESSION['user']['idPersonnel'] ?? 0);

        $ok = gestion_equipement_add(
            $idDossier,
            $idEquipement,
            $idPersonnelAction,
            'INFIRMIER'
        );

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
 * Signaler une panne côté infirmier.
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

    if (in_array($etat, ['en_panne', 'maintenance', 'HS'], true)) {
        $_SESSION['flash_error'] = "L'équipement est déjà indisponible.";
    } else {
        equipement_set_panne($idEquipement);

        require_once APP_PATH . '/models/AlerteModel.php';

        alerte_create(
            'panne_Equipement',
            "Équipement {$equipement['typeEquipement']} n°{$equipement['numeroEquipement']} déclaré en panne.",
            "index.php?action=equipement_detail_technicien&idEquipement={$idEquipement}"
        );

        $_SESSION['flash_success'] = "Panne signalée.";
    }

    header('Location: index.php?action=equipements_list_infirmier');
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
        $idDossier = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier = (int)($_GET['idDossier'] ?? 0);
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
 * Libérer un équipement côté infirmier.
 *
 * Cette action permet :
 * - de vérifier les droits utilisateur ;
 * - de récupérer les paramètres depuis POST ou GET ;
 * - de contrôler que l'équipement est bien à l'état "occupe" ;
 * - de remettre l'équipement à l'état "disponible" ;
 * - de rediriger avec un message utilisateur.
 */
function equipement_liberer(): void
{
    requireRole('INFIRMIER');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idEquipement = (int)($_POST['idEquipement'] ?? 0);
        $idDossier = (int)($_POST['idDossier'] ?? 0);
    } else {
        $idEquipement = (int)($_GET['idEquipement'] ?? 0);
        $idDossier = (int)($_GET['idDossier'] ?? 0);
    }

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header('Location: index.php?action=dossiers_list');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement || ($equipement['etatEquipement'] ?? '') !== 'occupe') {
        $_SESSION['flash_error'] = "Seul un équipement occupé peut être libéré.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    equipement_update_etat($idEquipement, 'disponible');

    $_SESSION['flash_success'] = "Équipement libéré.";
    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}


/* ======================================================================
   ACTIONS TECHNICIEN
   ====================================================================== */

/**
 * Afficher la liste des équipements côté technicien.
 */
function equipements_liste_technicien(): void
{
    requireRole('TECHNICIEN');

    $equipements = equipements_get_all_for_technicien();

    require APP_PATH . '/views/equipements/liste_technicien.php';
}

/**
 * Afficher le détail d'un équipement côté technicien.
 */
function equipement_detail_technicien(): void
{
    requireRole('TECHNICIEN');

    $idEquipement = (int)($_GET['idEquipement'] ?? ($_GET['id'] ?? 0));

    if ($idEquipement <= 0) {
        $_SESSION['flash_error'] = "ID équipement invalide.";
        header('Location: index.php?action=equipements_technicien');
        exit;
    }

    $equipement = equipement_get_detail_for_technicien($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_technicien');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération de la dernière maintenance pour enrichir la vue
    |--------------------------------------------------------------------------
    */
    $maintenance = equipement_get_last_maintenance($idEquipement);

    require APP_PATH . '/views/equipements/detail_technicien.php';
}

/**
 * Changer l'état d'un équipement côté technicien.
 */
function equipement_changer_etat(): void
{
    requireRole('TECHNICIEN');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $etat = trim((string)($_POST['etat'] ?? ''));
    $probleme = trim((string)($_POST['probleme'] ?? 'Diagnostic / intervention technique'));

    $redirect = 'index.php?action=equipement_detail_technicien&idEquipement=' . $idEquipement;

    if ($idEquipement <= 0 || $etat === '') {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=equipements_technicien');
        exit;
    }

    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        $_SESSION['flash_error'] = "Équipement introuvable.";
        header('Location: index.php?action=equipements_technicien');
        exit;
    }

    $etatActuel = (string)($equipement['etatEquipement'] ?? '');
    $allowed = false;

    /*
    |--------------------------------------------------------------------------
    | Transitions autorisées pour le technicien
    |--------------------------------------------------------------------------
    | - en_panne    -> maintenance
    | - maintenance -> disponible ou HS
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

    require_once APP_PATH . '/models/LitModel.php';

    $idPersonnel = (int)($_SESSION['user']['idPersonnel'] ?? 0);
    $idTechnicien = getTechnicienIdByPersonnel($idPersonnel);

    if ($idTechnicien <= 0) {
        $_SESSION['flash_error'] = "Technicien introuvable.";
        header('Location: ' . $redirect);
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        /*
        ----------------------------------------------------------------------
        | Si l'équipement passe en maintenance, on ouvre une maintenance
        ----------------------------------------------------------------------
        */
        if ($etat === 'maintenance') {
            maintenance_equipement_open($idEquipement, $idTechnicien, $probleme);
        }

        /*
        ----------------------------------------------------------------------
        | Si la maintenance se termine, on ferme l'intervention en cours
        ----------------------------------------------------------------------
        */
        if (in_array($etat, ['disponible', 'HS'], true)) {
            maintenance_equipement_close_open($idEquipement);
        }

        /*
        ----------------------------------------------------------------------
        | Mise à jour de l'état principal de l'équipement
        ----------------------------------------------------------------------
        */
        equipement_update_etat($idEquipement, $etat);

        /*
        ----------------------------------------------------------------------
        | Création d'une alerte si l'équipement devient HS
        ----------------------------------------------------------------------
        */
        if ($etat === 'HS') {
            require_once APP_PATH . '/models/AlerteModel.php';

            alerte_create(
                'panne_Equipement',
                "Équipement {$equipement['typeEquipement']} n°{$equipement['numeroEquipement']} déclaré HS par le technicien.",
                "index.php?action=equipement_detail_technicien&idEquipement={$idEquipement}"
            );
        }

        $pdo->commit();

        $_SESSION['flash_success'] = "État de l’équipement mis à jour.";
        header('Location: ' . $redirect);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : " . $e->getMessage();
        header('Location: ' . $redirect);
        exit;
    }
}
