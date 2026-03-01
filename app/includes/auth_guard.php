<?php
declare(strict_types=1);

// ===============================
// AUTH GUARD GLOBAL
// ===============================
// Vérifie que l'utilisateur est connecté
// et (optionnel) qu'il a le bon rôle

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Non connecté ?
if (!isset($_SESSION['user'])) {
    header('Location: index.php?action=login_form');
    exit;
}

// Vérification du rôle si nécessaire
function requireRole(string $role): void
{
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        echo "Accès interdit.";
        exit;
    }
}