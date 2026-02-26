<?php
declare(strict_types=1); 
// Active le typage strict pour éviter les erreurs de type

// ===============================
// FRONT CONTROLLER (MVC)
// ===============================
// Ce fichier est le point d’entrée unique de l’application.
// Toutes les requêtes passent par ici.
// Il décide quel contrôleur exécuter selon le paramètre ?action=

session_start(); 
// Démarre la session.
// Nécessaire pour gérer l’authentification (login/logout).
// Doit être appelé avant tout affichage HTML.

// Définition des chemins principaux du projet
// Pour éviter d’écrire des chemins relatifs compliqués
define('BASE_PATH', dirname(__DIR__)); 
// Chemin racine du projet

define('APP_PATH', BASE_PATH . '/app'); 
// Chemin vers le dossier app

// Chargement de la configuration base de données
// Permet d’utiliser la fonction db() dans les modèles
require_once APP_PATH . '/config/database.php';

// On récupère l’action depuis l’URL
// Exemple : index.php?action=login
// Si rien n’est défini → on affiche le formulaire login
$action = $_GET['action'] ?? 'login_form';

switch ($action) {

    // ===== AUTHENTIFICATION =====

    case 'login_form':
        // Affiche le formulaire de connexion
        require_once APP_PATH . '/controllers/AuthController.php';
        loginForm();
        break;

    case 'login':
        // Traite les données envoyées par le formulaire
        require_once APP_PATH . '/controllers/AuthController.php';
        login();
        break;

    case 'logout':
        // Déconnecte l’utilisateur et détruit la session
        require_once APP_PATH . '/controllers/AuthController.php';
        logout();
        break;

    // ===== DASHBOARD (page protégée) =====

    case 'dashboard':
        // Vérifie si l’utilisateur est connecté
        require_once APP_PATH . '/includes/auth_guard.php';

        // Si autorisé → appelle le contrôleur du dashboard
        require_once APP_PATH . '/controllers/DashboardController.php';
        dashboard();
        break;





    // ===== DOSSIERS (page protégée) =====

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

    // ===== ACTION INCONNUE =====

    default:
        // Si l’action n’existe pas → erreur 404
        http_response_code(404);
        echo "404 - Page introuvable";
        break;
}