<?php
declare(strict_types=1);
// Active le typage strict pour éviter les erreurs de type.

// ===============================
// FRONT CONTROLLER (MVC)
// ===============================
// Ce fichier est le point d’entrée unique de l’application.
// Toutes les requêtes passent par ici.
// Il choisit quel contrôleur exécuter selon le paramètre ?action=

session_start();
// Démarre la session.
// Nécessaire pour l’authentification et les messages flash.
// Doit être appelé avant tout affichage HTML.

// ===============================
// CONSTANTES DE CHEMINS
// ===============================
// Ces constantes permettent d’éviter les chemins relatifs compliqués.
define('BASE_PATH', dirname(__DIR__));     // Racine du projet
define('APP_PATH', BASE_PATH . '/app');    // Dossier principal de l’application

// ===============================
// CONFIGURATION GLOBALE
// ===============================
// Charge la connexion à la base de données et la fonction db().
require_once APP_PATH . '/config/database.php';

// ===============================
// ACTION DEMANDÉE
// ===============================
// Exemple : index.php?action=login
// Si aucune action n’est donnée, on affiche le formulaire de connexion.
$action = $_GET['action'] ?? 'login_form';

switch ($action) {

    // ==================================================
    // 1) AUTHENTIFICATION
    // ==================================================

    case 'login_form':
        // Affiche le formulaire de connexion.
        require_once APP_PATH . '/controllers/AuthController.php';
        loginForm();
        break;

    case 'login':
        // Traite la soumission du formulaire de connexion.
        require_once APP_PATH . '/controllers/AuthController.php';
        login();
        break;

    case 'logout':
        // Déconnecte l’utilisateur et détruit la session.
        require_once APP_PATH . '/controllers/AuthController.php';
        logout();
        break;


    // ==================================================
    // 2) TABLEAU DE BORD
    // ==================================================

    case 'dashboard':
        // Page protégée : utilisateur connecté obligatoire.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DashboardController.php';
        dashboard();
        break;


    // ==================================================
    // 3) DOSSIERS PATIENTS
    // Workflow :
    // liste -> détail -> création / modification
    // ==================================================

    case 'dossiers_list':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossiers_list();
        break;

    case 'dossier_detail':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_detail();
        break;

    case 'dossier_create_form':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_create_form();
        break;

    case 'dossier_create':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_create();
        break;

    case 'dossier_edit_form':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_edit_form();
        break;

    case 'dossier_update':
        require_once APP_PATH . '/includes/auth_guard.php';
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

    // ==================================================
    // 4) GESTION DES LITS
    // Workflow :
    // dashboard lits -> réservation -> gestion infirmier
    // ==================================================

    case 'lits_dashboard':
        // Protection ajoutée : accès réservé aux utilisateurs connectés.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/LitController.php';
        lits_dashboard();
        break;

    case 'lit_reserver_form':
        // Protection ajoutée : accès réservé aux utilisateurs connectés.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/LitController.php';
        lit_reserver_form();
        break;

    case 'lit_reserver':
        // Protection ajoutée : accès réservé aux utilisateurs connectés.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/LitController.php';
        lit_reserver();
        break;

    case 'lits_list_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/LitController.php';
        lits_list_infirmier();
        break;

    case 'lit_changer_etat_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/LitController.php';
        lit_changer_etat_infirmier();
        break;


    // ==================================================
    // 5) DOSSIER INFIRMIER - ACTIONS MÉTIER
    // Workflow :
    // confirmer installation -> faire avancer le dossier
    // ==================================================

    case 'confirmer_installation_patient':
        // Protection ajoutée : accès réservé aux utilisateurs connectés.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        confirmer_installation_patient();
        break;


    // ==================================================
    // 6) DOSSIER MÉDECIN - ACTIONS MÉTIER
    // Workflow :
    // commencer consultation -> analyser résultats -> suite du parcours
    // ==================================================

    case 'dossier_detail_medecin':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_detail_medecin();
        break;

    case 'dossier_commencer_consultation':
        // Protection ajoutée : accès réservé aux utilisateurs connectés.
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_commencer_consultation();
        break;

    case 'dossier_demander_transfert':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/DossierController.php';
        dossier_demander_transfert();
        break;


    // ==================================================
    // 7) ÉQUIPEMENTS - INFIRMIER
    // Workflow :
    // liste -> réservation -> signalement -> changement d’état
    // ==================================================

    case 'equipements_list_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipements_list_infirmier();
        break;

    case 'equipement_reserver_form_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_form_infirmier();
        break;

    case 'equipement_reserver_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_infirmier();
        break;

    case 'equipement_signaler_panne_infirmier':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_signaler_panne_infirmier();
        break;

    case 'equipement_utiliser':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_utiliser();
        break;

    case 'equipement_liberer':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_liberer();
        break;


    // ==================================================
    // 8) ÉQUIPEMENTS - MÉDECIN
    // Workflow :
    // liste -> formulaire -> réservation -> signalement
    // ==================================================

    case 'equipements_list_medecin':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipements_list_medecin();
        break;

    case 'equipement_reserver_form':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver_form();
        break;

    case 'equipement_reserver':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_reserver();
        break;

    case 'equipement_signaler_panne':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/EquipementController.php';
        equipement_signaler_panne();
        break;


    // ==================================================
    // 9) EXAMENS
    // Workflow :
    // demande -> création -> réalisation -> saisie du résultat
    // ==================================================

    case 'examen_form':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_form();
        break;

    case 'examen_create':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_create_action();
        break;

    case 'examen_realiser':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_realiser_action();
        break;

    case 'examen_saisir_resultat':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/ExamenController.php';
        examen_saisir_resultat_action();
        break;


    // ==================================================
    // 10) TRANSFERTS
    // Workflow :
    // formulaire -> création -> traitement par le directeur -> mise à jour
    // ==================================================

    case 'transfert_form':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_form();
        break;

    case 'transfert_create':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_create_action();
        break;

    case 'transferts_traitement_directeur':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/TransfertController.php';
        transferts_traitement_directeur();
        break;

    case 'transfert_update_statut':
        require_once APP_PATH . '/includes/auth_guard.php';
        require_once APP_PATH . '/controllers/TransfertController.php';
        transfert_update_statut_action();
        break;


    // ==================================================
    // 11) ACTION INCONNUE
    // ==================================================

    default:
        // Si l’action demandée n’existe pas, on renvoie une erreur 404.
        http_response_code(404);
        echo "404 - Page introuvable";
        break;
}