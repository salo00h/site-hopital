<?php
declare(strict_types=1);

/*
==================================================
  CONTROLLER : AuthController
==================================================
  Rôle :
  - Afficher le formulaire de connexion
  - Vérifier l'identifiant et le mot de passe
  - Gérer la déconnexion

  Architecture :
  - Appel du Model : UserModel
  - Chargement des vues correspondantes
==================================================
*/

require_once __DIR__ . '/../models/UserModel.php';


/**
 * Afficher le formulaire de connexion
 */
function loginForm(): void
{
    $error = '';

    require __DIR__ . '/../views/auth/login.php';
}


/**
 * Traiter la connexion utilisateur
 */
function login(): void
{
    // Lire les données du formulaire
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérifier les champs obligatoires
    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    // Rechercher l'utilisateur dans la base
    $user = findUserByUsername($username);

    // Vérifier le mot de passe
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    // Créer la session utilisateur
    $_SESSION['user'] = [
        'idUser'      => (int) $user['idUser'],
        'idPersonnel' => (int) $user['idPersonnel'],
        'username'    => (string) $user['username'],
        'role'        => (string) $user['role']
    ];

    // Redirection vers le dashboard
    header('Location: index.php?action=dashboard');
    exit;
}


/**
 * Déconnexion de l'utilisateur
 */
function logout(): void
{
    // Supprimer la session
    session_destroy();

    // Retour vers la page de connexion
    header('Location: index.php?action=login_form');
    exit;
}