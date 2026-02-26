<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion - SI Hôpital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-card">
        <h1 class="login-title">Connexion</h1>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?action=login">
            <label>Nom d'utilisateur</label>
            <input type="text" name="username" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>