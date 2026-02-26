<?php
declare(strict_types=1);

function dashboard(): void
{
    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        header('Location: index.php?action=login_form');
        exit;
    }

    $role = $user['role'] ?? '';

    switch ($role) {
        case 'INFIRMIER_ACCUEIL':
            require __DIR__ . '/../views/dashboards/accueil_infirmier_accueil.php';
            break;

        case 'INFIRMIER':
            require __DIR__ . '/../views/dashboards/accueil_infirmier.php';
            break;

        case 'MEDECIN':
            require __DIR__ . '/../views/dashboards/accueil_medecin.php';
            break;

        case 'DIRECTEUR':
            require __DIR__ . '/../views/dashboards/accueil_directeur.php';
            break;

        case 'RESPONSABLE_REGIONAL':
            require __DIR__ . '/../views/dashboards/accueil_responsable_regional.php';
            break;

        case 'TECHNICIEN':
            require __DIR__ . '/../views/dashboards/accueil_technicien.php';
            break;

        default:
            http_response_code(403);
            echo "Role inconnu";
            break;
    }
}