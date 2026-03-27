<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FRONT CONTROLLER DE L'APPLICATION
|--------------------------------------------------------------------------
| Ce fichier constitue le point d'entrée principal du projet.
| Toutes les requêtes HTTP transitent par ce front controller.
| Il centralise le routage et redirige les demandes vers les contrôleurs
| appropriés selon la valeur du paramètre "action".
|
| Ce fichier est sensible car il représente le point d'accès central
| de l'application. Il doit donc rester lisible, stable, cohérent et
| facile à maintenir.
|
| Les actions sont organisées par domaine fonctionnel afin de rendre
| la structure du switch plus claire et plus simple à parcourir.
|--------------------------------------------------------------------------
*/

session_start();

/*
|--------------------------------------------------------------------------
| CONSTANTES DE CHEMINS
|--------------------------------------------------------------------------
| Ces constantes permettent de centraliser les chemins principaux
| du projet et d'éviter la multiplication de chemins relatifs.
|--------------------------------------------------------------------------
*/
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

/*
|--------------------------------------------------------------------------
| CONFIGURATION GLOBALE
|--------------------------------------------------------------------------
| Chargement de la configuration principale, notamment la connexion
| à la base de données et la fonction d'accès PDO.
|--------------------------------------------------------------------------
*/
require_once APP_PATH . '/config/database.php';

/*
|--------------------------------------------------------------------------
| ACTION DEMANDÉE
|--------------------------------------------------------------------------
| Si aucune action n'est fournie, le formulaire de connexion est
| affiché par défaut.
|--------------------------------------------------------------------------
*/
$action = $_GET['action'] ?? 'login_form';

/*
|--------------------------------------------------------------------------
| GARDE D'AUTHENTIFICATION
|--------------------------------------------------------------------------
| Le contrôle d'accès est centralisé ici afin d'éviter de répéter
| la même vérification dans chaque branche du routage.
|--------------------------------------------------------------------------
*/
require_once APP_PATH . '/includes/auth_guard.php';

/*
|--------------------------------------------------------------------------
| ACTIONS PUBLIQUES
|--------------------------------------------------------------------------
| Ces actions restent accessibles sans authentification préalable.
|--------------------------------------------------------------------------
*/
$publicActions = [
    'login_form',
    'login',
    'logout',
];

/*
|--------------------------------------------------------------------------
| PROTECTION DES ACTIONS PRIVÉES
|--------------------------------------------------------------------------
| Toute action qui ne figure pas dans la liste publique nécessite
| une session utilisateur active.
|--------------------------------------------------------------------------
*/
if (!in_array($action, $publicActions, true)) {
    if (empty($_SESSION['user'])) {
        $_SESSION['flash_error'] = 'Veuillez vous connecter.';
        header('Location: index.php?action=login_form');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| ROUTAGE PRINCIPAL
|--------------------------------------------------------------------------
| Le switch centralise la répartition des requêtes vers les différents
| contrôleurs. Sa structure doit rester claire, ordonnée et stable.
|--------------------------------------------------------------------------
*/
switch ($action) {
    /*
    |--------------------------------------------------------------------------
    | 1. AUTHENTIFICATION
    |--------------------------------------------------------------------------
    | Gestion de l'accès à l'application :
    | - affichage du formulaire
    | - connexion
    | - déconnexion
    |--------------------------------------------------------------------------
    */
    case 'login_form':
        require_once APP_PATH . '/controllers/AuthController.php';
        loginForm();
        break;

    case 'login':
        require_once APP_PATH . '/controllers/AuthController.php';
        login();
        break;

    case 'logout':
        require_once APP_PATH . '/controllers/AuthController.php';
        logout();
        break;

    /*
    |--------------------------------------------------------------------------
    | 2. TABLEAU DE BORD
    |--------------------------------------------------------------------------
    | Point d'accès principal après authentification.
    |--------------------------------------------------------------------------
    */
    case 'dashboard':
        require_once APP_PATH . '/controllers/DashboardController.php';
        dashboard();
        break;

    /*
    |--------------------------------------------------------------------------
    | 3. DOSSIERS PATIENTS
    |--------------------------------------------------------------------------
    | Domaine fonctionnel lié à la gestion administrative et médicale
    | des dossiers patients.
    |--------------------------------------------------------------------------
    */
    case 'dossiers_list':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossiers_list();
        break;

    case 'dossier_detail':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_detail();
        break;

    case 'dossier_create_form':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_create_form();
        break;

    case 'dossier_create':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_create();
        break;

    case 'dossier_edit_form':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_edit_form();
        break;

    case 'dossier_update':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_update();
        break;

    case 'validerSortieMedecin':
        require_once APP_PATH . '/controllers/DossierController.php';
        validerSortieMedecin();
        break;

    case 'confirmerSortieInfirmier':
        require_once APP_PATH . '/controllers/DossierController.php';
        confirmerSortieInfirmier();
        break;

    /*
    |--------------------------------------------------------------------------
    | 4. GESTION DES LITS
    |--------------------------------------------------------------------------
    | Actions liées à l'affichage, la réservation et la gestion métier
    | des lits.
    |--------------------------------------------------------------------------
    */
    case 'lits_dashboard':
        require_once APP_PATH . '/controllers/LitController.php';
        lits_dashboard();
        break;

    case 'lit_reserver_form':
        require_once APP_PATH . '/controllers/LitController.php';
        lit_reserver_form();
        break;

    case 'lit_reserver':
        require_once APP_PATH . '/controllers/LitController.php';
        lit_reserver();
        break;

    case 'lits_list_infirmier':
        require_once APP_PATH . '/controllers/LitController.php';
        lits_list_infirmier();
        break;

    case 'lit_changer_etat_infirmier':
        require_once APP_PATH . '/controllers/LitController.php';
        lit_changer_etat_infirmier();
        break;

    /*
    |--------------------------------------------------------------------------
    | 5. DOSSIER INFIRMIER
    |--------------------------------------------------------------------------
    | Actions métier spécifiques au suivi infirmier.
    |--------------------------------------------------------------------------
    */
    case 'confirmer_installation_patient':
        require_once APP_PATH . '/controllers/DossierController.php';
        confirmer_installation_patient();
        break;

    /*
    |--------------------------------------------------------------------------
    | 6. DOSSIER MÉDECIN
    |--------------------------------------------------------------------------
    | Actions métier spécifiques au parcours médical et à la consultation.
    |--------------------------------------------------------------------------
    */
    case 'dossier_detail_medecin':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_detail_medecin();
        break;

    case 'dossier_commencer_consultation':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_commencer_consultation();
        break;

    case 'dossier_demander_transfert':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_demander_transfert();
        break;

    case 'dossier_edit_medecin_form':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_edit_medecin_form();
        break;

    case 'dossier_update_medecin':
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_update_medecin();
        break;

    /*
    |--------------------------------------------------------------------------
    | 7. ÉQUIPEMENTS - INFIRMIER
    |--------------------------------------------------------------------------
    | Actions liées aux équipements manipulés dans le cadre infirmier.
    |--------------------------------------------------------------------------
    */
    case 'equipements_list_infirmier':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipements_list_infirmier();
        break;

    case 'equipement_reserver_form_infirmier':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_form_infirmier();
        break;

    case 'equipement_reserver_infirmier':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_infirmier();
        break;

    case 'equipement_signaler_panne_infirmier':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_signaler_panne_infirmier();
        break;

    case 'equipement_utiliser':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_utiliser();
        break;

    case 'equipement_liberer':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_liberer();
        break;

    /*
    |--------------------------------------------------------------------------
    | 8. ÉQUIPEMENTS - MÉDECIN
    |--------------------------------------------------------------------------
    | Actions liées aux équipements dans le contexte médical.
    |--------------------------------------------------------------------------
    */
    case 'equipements_list_medecin':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipements_list_medecin();
        break;

    case 'equipement_reserver_form':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_form();
        break;

    case 'equipement_reserver':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver();
        break;

    case 'equipement_signaler_panne':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_signaler_panne();
        break;

    case 'equipement_utiliser_medecin':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_utiliser_medecin();
        break;

    case 'equipement_liberer_medecin':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_liberer_medecin();
        break;

    /*
    |--------------------------------------------------------------------------
    | 9. EXAMENS
    |--------------------------------------------------------------------------
    | Routage des actions liées aux demandes, réalisations et résultats
    | d'examens médicaux.
    |--------------------------------------------------------------------------
    */
    case 'examen_form':
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_form();
        break;

    case 'examen_create':
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_create_action();
        break;

    case 'examen_realiser':
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_realiser_action();
        break;

    case 'examen_saisir_resultat':
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_saisir_resultat_action();
        break;

    /*
    |--------------------------------------------------------------------------
    | 10. TRANSFERTS
    |--------------------------------------------------------------------------
    | Gestion du cycle de vie des demandes de transfert.
    |--------------------------------------------------------------------------
    */
    case 'transfert_form':
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_form();
        break;

    case 'transfert_create':
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_create_action();
        break;

    case 'transferts_traitement_directeur':
        require_once APP_PATH . '/controllers/TransfertController.php';
        transferts_traitement_directeur();
        break;

    case 'transfert_update_statut':
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_update_statut_action();
        break;

    case 'transferts_historique':
        require_once APP_PATH . '/controllers/TransfertController.php';
        transferts_historique();
        break;

    /*
    |--------------------------------------------------------------------------
    | 11. TECHNICIEN
    |--------------------------------------------------------------------------
    | Domaine regroupant les actions de maintenance et de consultation
    | technique sur les lits et les équipements.
    |--------------------------------------------------------------------------
    */
    case 'tech_dashboard':
        require_once APP_PATH . '/controllers/DashboardController.php';
        technicien_dashboard();
        break;

    case 'lits_technicien':
        require_once APP_PATH . '/controllers/LitController.php';
        lits_liste_technicien();
        break;

    case 'lit_detail_technicien':
        require_once APP_PATH . '/controllers/LitController.php';
        lit_detail_technicien();
        break;

    case 'lit_changer_etat':
        require_once APP_PATH . '/controllers/LitController.php';
        lit_changer_etat();
        break;

    case 'equipements_technicien':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipements_liste_technicien();
        break;

    case 'equipement_detail_technicien':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_detail_technicien();
        break;

    case 'equipement_changer_etat':
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_changer_etat();
        break;

    /*
    |--------------------------------------------------------------------------
    | 12. ACTION INCONNUE
    |--------------------------------------------------------------------------
    | Toute action non reconnue retourne une erreur 404.
    |--------------------------------------------------------------------------
    */
    default:
        http_response_code(404);
        echo '404 - Page introuvable';
        break;
}
