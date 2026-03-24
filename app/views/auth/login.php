<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion - SI Hôpital</title>

    <!-- On lie le fichier CSS pour le style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Conteneur principal pour centrer la page -->
<div class="login-wrapper">

    <!-- Boîte qui contient toute la page de connexion -->
    <div class="login-container">

        <!-- Barre bleue en haut -->
        <div class="login-topbar">
            <div class="login-brand">

                <!-- Ici on peut mettre le logo plus tard -->
                <span class="logo-placeholder">HRMS</span>

                <!-- Nom du système -->
                <span>HRMS – Connexion au système</span>
            </div>
        </div>

        <!-- Partie du formulaire -->
        <div class="login-body">

            <!-- Titre de la page -->
            <h2 class="login-subtitle">Portail de connexion</h2>

            <!-- Message d'erreur venant de la session -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <!-- On supprime le message après affichage -->
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

                <!-- Champ pour le nom utilisateur -->
                <label>Identifiant</label>
                <input type="text" name="username" placeholder="Votre identifiant" required>

                <!-- Champ pour le mot de passe -->
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Votre mot de passe" required>

                <!-- Bouton pour envoyer le formulaire -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Me connecter</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
