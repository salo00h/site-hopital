<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification connexion
if (empty($_SESSION['user'])) {
    $_SESSION['flash_error'] = "Veuillez vous connecter.";
    header('Location: index.php?action=login_form');
    exit;
}

// Vérification rôle
function requireRole(string ...$roles): void
{
    $userRole = $_SESSION['user']['role'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        echo "403 - Accès interdit.";
        exit;
    }
}