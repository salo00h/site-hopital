<?php
declare(strict_types=1);

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

/**
 * Oblige la connexion avant accès
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Veuillez vous connecter.";
        header('Location: index.php?action=login_form');
        exit;
    }
}

/**
 * Vérifie que l'utilisateur a le bon rôle
 */
function requireRole(string ...$roles): void
{
    requireLogin();

    $userRole = $_SESSION['user']['role'] ?? '';

    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        exit("Accès interdit.");
    }
}