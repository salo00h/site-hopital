<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONTRÔLEUR : DashboardController
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - vérifier qu'un utilisateur est connecté ;
| - identifier son rôle ;
| - charger les modèles nécessaires selon ce rôle ;
| - préparer les données du dashboard ;
| - afficher la vue correspondante.
|
| Organisation du fichier :
| 1. Point d'entrée principal : dashboard()
| 2. Outils spécifiques au dashboard technicien
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/includes/auth_guard.php';


/* ======================================================================
   POINT D'ENTRÉE PRINCIPAL
   ====================================================================== */

/**
 * Contrôleur principal du dashboard.
 *
 * Cette fonction redirige l'utilisateur vers le tableau de bord
 * correspondant à son rôle métier.
 */
function dashboard(): void
{
    /*
    |--------------------------------------------------------------------------
    | Vérification : un utilisateur doit être connecté
    |--------------------------------------------------------------------------
    */
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération du rôle de l'utilisateur connecté
    |--------------------------------------------------------------------------
    */
    $role = $_SESSION['user']['role'] ?? '';

    switch ($role) {
        /* ==============================================================
           DASHBOARD INFIRMIER D'ACCUEIL
           ============================================================== */
        case 'INFIRMIER_ACCUEIL':
            requireRole('INFIRMIER_ACCUEIL');

            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';

            /*
            --------------------------------------------------------------
            | Le dashboard doit afficher uniquement les données
            | du service Urgences
            --------------------------------------------------------------
            */
            $idService = (int)(getServiceIdByName('Urgences') ?? 0);

            $stats = [
                'lits_disponibles'        => lits_count_disponibles_by_service($idService),
                'lits_reserves'           => lits_count_reserves_by_service($idService),
                'patients_consultation'   => dossiers_count_patients_consultation(),
                'patients_attente'        => dossiers_count_patients_attente(),
                'equipements_disponibles' => equipements_count_disponibles(),
                'alertes_total'           => alertes_count_all(),
                'niveau_1'                => dossiers_count_by_niveau(1),
                'niveau_2'                => dossiers_count_by_niveau(2),
                'niveau_3'                => dossiers_count_by_niveau(3),
                'message_alerte'          => 'Aucune alerte pour le moment.',
            ];

            $alertesRecentes = alertes_get_last(5);

            require APP_PATH . '/views/dashboards/accueil_infirmier_accueil.php';
            break;

        /* ==============================================================
           DASHBOARD INFIRMIER
           ============================================================== */
        case 'INFIRMIER':
            requireRole('INFIRMIER');

            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';

            /*
            --------------------------------------------------------------
            | Le dashboard infirmier doit afficher uniquement les données
            | du service Urgences
            --------------------------------------------------------------
            */
            $idService = (int)(getServiceIdByName('Urgences') ?? 0);

            $stats = [
                'lits_disponibles'      => lits_count_disponibles_by_service($idService),
                'lits_occupes'          => lits_count_occupes_et_reserves_by_service($idService),
                'patients_consultation' => dossiers_count_patients_consultation(),
                'patients_attente'      => dossiers_count_patients_attente(),
                'equipements_stats'     => equipements_get_stats(),
            ];

            $tauxOccupation = lits_get_taux_occupation_by_service($idService);
            $alertesRecentes = alertes_get_last(5);

            require APP_PATH . '/views/dashboards/accueil_infirmier.php';
            break;

        /* ==============================================================
           DASHBOARD MÉDECIN
           ============================================================== */
        case 'MEDECIN':
            requireRole('MEDECIN');

            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';
            require_once APP_PATH . '/models/ExamenModel.php';

            /*
            --------------------------------------------------------------
            | Données principales du dashboard médecin
            --------------------------------------------------------------
            */

            // Derniers dossiers
            $dossiersRecent = dossiers_get_recent(5);

            // Liste des consultations en cours / à afficher
            $consultations = dossiers_get_consultations();

            // Statistiques des équipements
            $equipementsStats = equipements_get_stats();
            $nbEquipementsDisponibles = equipements_count_disponibles();

            /*
            --------------------------------------------------------------
            | Le dashboard médecin doit afficher uniquement les données
            | du service Urgences
            --------------------------------------------------------------
            */
            $idService = (int)(getServiceIdByName('Urgences') ?? 0);

            // Statistiques des lits
            $litsStats = lits_get_stats($idService);
            $litsDisponibles = lits_count_disponibles_by_service($idService);
            $litsOccupes = lits_count_occupes_et_reserves_by_service($idService);

            // Examens en attente
            $nbExamensEnAttente = examens_count_en_attente();
            $examensEnAttente = examens_get_en_attente_with_patient(5);

            // Dernières alertes
            $alertes = alertes_get_last(5);

            // Taux d'occupation du service Urgences
            $tauxOccupation = lits_get_taux_occupation_by_service($idService);

            require APP_PATH . '/views/dashboards/accueil_medecin.php';
            break;

        /* ==============================================================
           DASHBOARD DIRECTEUR
           ============================================================== */
        case 'DIRECTEUR':
            requireRole('DIRECTEUR');

            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';
            require_once APP_PATH . '/models/TransfertModel.php';

            /*
            --------------------------------------------------------------
            | Le dashboard directeur doit afficher uniquement les données
            | du service Urgences
            --------------------------------------------------------------
            */
            $idService = (int)(getServiceIdByName('Urgences') ?? 0);

            // Statistiques des lits
            $litsDisponibles = lits_count_disponibles_by_service($idService);
            $litsOccupes = lits_count_occupes_et_reserves_by_service($idService);
            $tauxOccupation = lits_get_taux_occupation_by_service($idService);

            // Statistiques des équipements
            $equipementsStats = equipements_get_stats();

            // Dernières alertes
            $alertesRecentes = alertes_get_last(5);

            // Historique des transferts
            $historiqueTransferts = transferts_get_recent(5);

            require APP_PATH . '/views/dashboards/accueil_directeur.php';
            break;

        /* ==============================================================
           DASHBOARD RESPONSABLE RÉGIONAL
           ============================================================== */
        case 'RESPONSABLE_REGIONAL':
            require APP_PATH . '/views/dashboards/accueil_responsable_regional.php';
            break;

        /* ==============================================================
           DASHBOARD TECHNICIEN
           ============================================================== */
        case 'TECHNICIEN':
            technicien_dashboard();
            break;

        /* ==============================================================
           CAS PAR DÉFAUT
           ============================================================== */
        default:
            http_response_code(403);
            echo "Rôle non autorisé.";
            break;
    }
}


/* ======================================================================
   OUTILS SPÉCIFIQUES : DASHBOARD TECHNICIEN
   ====================================================================== */

if (!function_exists('technicien_dashboard_data')) {
    /**
     * Préparer les données du dashboard technicien.
     *
     * Cette fonction centralise :
     * - les statistiques des lits ;
     * - les statistiques des équipements ;
     * - les alertes récentes.
     */
    function technicien_dashboard_data(): array
    {
        require_once APP_PATH . '/models/LitModel.php';
        require_once APP_PATH . '/models/EquipementModel.php';
        require_once APP_PATH . '/models/AlerteModel.php';

        return [
            'statsLits' => [
                'disponible'  => lits_count_by_etat('disponible'),
                'panne'       => lits_count_by_etat('en_panne'),
                'maintenance' => lits_count_by_etat('maintenance'),
                'hs'          => lits_count_by_etat('HS'),
            ],
            'statsEquipements' => [
                'disponible'  => equipements_count_by_etat('disponible'),
                'panne'       => equipements_count_by_etat('en_panne'),
                'maintenance' => equipements_count_by_etat('maintenance'),
                'hs'          => equipements_count_by_etat('HS'),
            ],
            'alertesRecentes' => alertes_get_last(5),
        ];
    }
}

if (!function_exists('technicien_dashboard')) {
    /**
     * Afficher le dashboard du technicien.
     */
    function technicien_dashboard(): void
    {
        requireRole('TECHNICIEN');

        $data = technicien_dashboard_data();

        $statsLits = $data['statsLits'];
        $statsEquipements = $data['statsEquipements'];
        $alertesRecentes = $data['alertesRecentes'];

        require APP_PATH . '/views/dashboards/accueil_technicien.php';
    }
}
