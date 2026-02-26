<?php

declare(strict_types=1);

// Contrôleur d’authentification (partie C du MVC).
// Ce fichier gère :
// - l’affichage du formulaire de connexion
// - la vérification du login
// - la déconnexion
//
// Il communique avec le Model (UserModel)
// et charge la Vue correspondante.

require_once __DIR__ . '/../models/UserModel.php';

// Affiche la page de connexion
function loginForm(): void
{
    $error = '';
    require __DIR__ . '/../views/auth/login.php';
}

// Traite la connexion
function login(): void
{
    // Récupération des données envoyées par le formulaire
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérification que les champs ne sont pas vides
    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    // Recherche de l'utilisateur dans la base de données
    $user = findUserByUsername($username);

    // Vérification du mot de passe (sécurité)
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    // Création de la session après connexion réussie
    $_SESSION['user'] = [
        'idUser' => (int) $user['idUser'],
        'idPersonnel' => (int) $user['idPersonnel'],
        'username' => (string) $user['username'],
        'role' => (string) $user['role']
    ];

    // Redirection vers le dashboard
    header('Location: index.php?action=dashboard');
    exit;
}

// Déconnexion de l'utilisateur
function logout(): void
{
    session_destroy(); // Supprime la session
    header('Location: index.php?action=login_form');
    exit;
}