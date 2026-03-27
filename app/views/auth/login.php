<?php
/*
==================================================
 VUE : Page de connexion
==================================================
 Rôle :
 - Afficher le formulaire de connexion utilisateur
 - Afficher les messages d'erreur (session ou simple)

 Remarques d'organisation :
 - Ce fichier appartient uniquement à la couche View (MVC)
 - Ce fichier doit rester simple et lisible
 - Pas de logique métier ici
 - Pas de traitement de données ici
 - Pas de CSS ici (uniquement liaison du fichier existant)
 - Uniquement affichage et structure HTML claire
==================================================
*/
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion - SI Hôpital</title>

    <!-- On lie le fichier CSS principal -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Conteneur principal pour centrer toute la page -->
<div class="login-wrapper">

    <!-- Boîte principale de la page de connexion -->
    <div class="login-container">

        <!-- Barre supérieure -->
        <div class="login-topbar">
            <div class="login-brand">

                <!-- Logo du système -->
                <img src="assets/images/logo.png" alt="Logo HRMS" class="login-logo">

                <!-- Nom du système -->
                <span>HRMS – Connexion au système</span>
            </div>
        </div>

        <!-- Corps de la page -->
        <div class="login-body">

            <!-- Titre du formulaire -->
            <h2 class="login-subtitle">Portail de connexion</h2>

            <!-- Message d'erreur stocké dans la session -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- Message d'erreur simple -->
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form method="post" action="index.php?action=login">

                <!-- Champ identifiant -->
                <label>Identifiant</label>
                <input type="text" name="username" placeholder="Votre identifiant" required>

                <!-- Champ mot de passe -->
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Votre mot de passe" required>

                <!-- Bouton de connexion -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Me connecter</button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>