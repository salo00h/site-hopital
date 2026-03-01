<?php
/*
==================================================
 CONTRÔLEUR : DashboardController
==================================================
 Rôle :
 - Gérer l'affichage du tableau de bord selon le rôle
 - Préparer les données nécessaires avant l’appel de la vue
 - Respecter la séparation des responsabilités (MVC)

 Améliorations apportées :
 - Suppression des appels inutiles à function_exists()
 - Chargement des modèles uniquement pour le rôle MEDECIN
 - Clarification de la logique du switch
 - Code plus lisible et maintenable

 Remarque :
 Toute la logique métier reste dans les modèles.
 Aucune requête SQL ni HTML dans ce contrôleur.
==================================================
*/
declare(strict_types=1);

require_once APP_PATH . '/includes/auth_guard.php';

function dashboard(): void
{
    
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login_form');
        exit;
    }

    $role = $_SESSION['user']['role'] ?? '';

    switch ($role) {

        case 'INFIRMIER_ACCUEIL':
            require APP_PATH . '/views/dashboards/accueil_infirmier_accueil.php';
            break;

        case 'INFIRMIER':
            require APP_PATH . '/views/dashboards/accueil_infirmier.php';
            break;

        case 'MEDECIN':
            requireRole('MEDECIN');

            
            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';

           
            $dossiersRecent   = dossiers_get_recent(5);
            $equipementsStats = equipements_get_stats();
            $litsStats        = lits_get_stats();
            $alertes          = alertes_get_last(5);

            require APP_PATH . '/views/dashboards/accueil_medecin.php';
            break;

        case 'DIRECTEUR':
            require APP_PATH . '/views/dashboards/accueil_directeur.php';
            break;

        case 'RESPONSABLE_REGIONAL':
            require APP_PATH . '/views/dashboards/accueil_responsable_regional.php';
            break;

        case 'TECHNICIEN':
            require APP_PATH . '/views/dashboards/accueil_technicien.php';
            break;

        default:
            http_response_code(403);
            echo "Rôle non autorisé.";
            break;
    }
}