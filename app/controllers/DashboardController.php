<?php
declare(strict_types=1);

/*
==================================================
 CONTRÔLEUR : DashboardController
==================================================
*/

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
            requireRole('INFIRMIER_ACCUEIL');

            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';

            $stats = [
                'lits_disponibles'        => lits_count_disponibles(),
                'lits_reserves'           => lits_count_reserves(),
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

        case 'INFIRMIER':
            requireRole('INFIRMIER');

            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';

            $stats = [
                'lits_disponibles'      => lits_count_disponibles(),
                'lits_occupes'          => lits_count_occupes_et_reserves(),
                'patients_consultation' => dossiers_count_patients_consultation(),
                'patients_attente'      => dossiers_count_patients_attente(),
                'equipements_stats'     => equipements_get_stats(),
            ];

            $tauxOccupation = lits_get_taux_occupation_global();
            $alertesRecentes = alertes_get_last(5);

            require APP_PATH . '/views/dashboards/accueil_infirmier.php';
            break;

        case 'MEDECIN':
            requireRole('MEDECIN');

            require_once APP_PATH . '/models/DossierModel.php';
            require_once APP_PATH . '/models/EquipementModel.php';
            require_once APP_PATH . '/models/LitModel.php';
            require_once APP_PATH . '/models/AlerteModel.php';
            require_once APP_PATH . '/models/ExamenModel.php';

            /*
            -----------------------------------------
            Données principales du dashboard médecin
            -----------------------------------------
            */

            // Derniers dossiers
            $dossiersRecent = dossiers_get_recent(5);

            // Liste des consultations à venir
            $consultations = dossiers_get_consultations();

            // Statistiques des équipements
            $equipementsStats = equipements_get_stats();
            $nbEquipementsDisponibles = equipements_count_disponibles();

            // Statistiques des lits
            $litsStats = lits_get_stats();
            $litsDisponibles = lits_count_disponibles();
            $litsOccupes = lits_count_occupes_et_reserves();

            // Dossiers en attente d'examen
            $nbExamensEnAttente = examens_count_en_attente();
            $examensEnAttente = examens_get_en_attente_with_patient(5);

            // Dernières alertes
            $alertes = alertes_get_last(5);

            // Calcul du taux d'occupation
            $totalLits = $litsDisponibles + $litsOccupes;

            if ($totalLits > 0) {
                $tauxOccupation = round(($litsOccupes / $totalLits) * 100);
            } else {
                $tauxOccupation = 0;
            }

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




