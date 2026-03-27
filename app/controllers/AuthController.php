<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONTROLLER : AuthController
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - afficher le formulaire de connexion ;
| - vérifier les identifiants saisis ;
| - ouvrir la session utilisateur ;
| - enregistrer les informations utiles en session ;
| - gérer la déconnexion.
|
| Organisation du fichier :
| 1. Chargements communs
| 2. Actions d'authentification
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../models/UserModel.php';


/* ======================================================================
   ACTIONS D'AUTHENTIFICATION
   ====================================================================== */

/**
 * Afficher le formulaire de connexion.
 */
function loginForm(): void
{
    /*
    |--------------------------------------------------------------------------
    | Démarrer la session si elle n'est pas encore active
    |--------------------------------------------------------------------------
    */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /*
    |--------------------------------------------------------------------------
    | Message d'erreur vide lors du premier affichage
    |--------------------------------------------------------------------------
    */
    $error = '';

    /*
    |--------------------------------------------------------------------------
    | Chargement de la vue du formulaire
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/../views/auth/login.php';
}

/**
 * Traiter la connexion utilisateur.
 */
function login(): void
{
    /*
    |--------------------------------------------------------------------------
    | Démarrer la session si nécessaire
    |--------------------------------------------------------------------------
    */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /*
    |--------------------------------------------------------------------------
    | Refuser l'accès direct si la méthode HTTP n'est pas POST
    |--------------------------------------------------------------------------
    */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?action=login_form');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Récupération et nettoyage des données du formulaire
    |--------------------------------------------------------------------------
    */
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Vérification des champs obligatoires
    |--------------------------------------------------------------------------
    */
    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Recherche de l'utilisateur par son nom d'utilisateur
    |--------------------------------------------------------------------------
    */
    $user = findUserByUsername($username);

    /*
    |--------------------------------------------------------------------------
    | Vérification :
    | - l'utilisateur doit exister ;
    | - le mot de passe doit correspondre au hash enregistré.
    |--------------------------------------------------------------------------
    */
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
        require __DIR__ . '/../views/auth/login.php';
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Enregistrement des informations utiles en session
    |--------------------------------------------------------------------------
    | On conserve uniquement les données nécessaires au fonctionnement
    | de l'application et au contrôle des rôles.
    |--------------------------------------------------------------------------
    */
    $_SESSION['user'] = [
        'idUser'      => (int)$user['idUser'],
        'idPersonnel' => (int)$user['idPersonnel'],
        'username'    => (string)$user['username'],
        'nom'         => (string)$user['nom'],
        'prenom'      => (string)$user['prenom'],
        'role'        => (string)$user['role'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Redirection vers le tableau de bord après connexion réussie
    |--------------------------------------------------------------------------
    */
    header('Location: index.php?action=dashboard');
    exit;
}

/**
 * Déconnecter l'utilisateur.
 */
function logout(): void
{
    /*
    |--------------------------------------------------------------------------
    | Démarrer la session si nécessaire
    |--------------------------------------------------------------------------
    */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /*
    |--------------------------------------------------------------------------
    | Vider les données de session
    |--------------------------------------------------------------------------
    */
    $_SESSION = [];

    /*
    |--------------------------------------------------------------------------
    | Détruire complètement la session
    |--------------------------------------------------------------------------
    */
    session_destroy();

    /*
    |--------------------------------------------------------------------------
    | Retour à la page de connexion
    |--------------------------------------------------------------------------
    */
    header('Location: index.php?action=login_form');
    exit;
}