<?php

declare(strict_types=1);

// Protection de la page.
// Cette page est réservée aux utilisateurs connectés.
// On vérifie si la session "user" existe.
// La session "user" est créée après un login réussi.
//
// Si elle n’existe pas, cela veut dire que
// la personne n’a pas fait la connexion.
// Donc on la redirige vers la page de login.
//
// exit est important pour arrêter le script
// après la redirection.

if (!isset($_SESSION['user'])) {
    header('Location: index.php?action=login_form');
    exit;
}