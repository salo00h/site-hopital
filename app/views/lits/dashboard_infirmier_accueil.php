<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Vue : lits du service des urgences
|--------------------------------------------------------------------------
| Ce fichier contient uniquement l'affichage.
| Il doit rester simple, lisible et centré sur la structure HTML/PHP.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement la présentation des données reçues.
|--------------------------------------------------------------------------
*/
?>

<h1 class="page-title">Lits du service des urgences</h1>
<p class="page-subtitle">Vue des lits du service des urgences uniquement.</p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($alertMessage)): ?>
    <div class="alert <?= ($alertLevel === 'danger') ? 'alert-danger' : 'alert-warning' ?>">
        <?= htmlspecialchars($alertMessage) ?>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Lits disponibles</div>
        <div class="stat-value"><?= (int) $nbDisponible ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Lits occupés</div>
        <div class="stat-value"><?= (int) $nbOccupe ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Lits réservés</div>
        <div class="stat-value"><?= (int) $nbReserve ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Taux d’occupation global</div>
        <div class="stat-value"><?= (int) $tauxOccupation ?>%</div>

        <div class="progress">
            <div
                class="progress-bar"
                style="width: <?= (int) $tauxOccupation ?>%"
            ></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-title">Liste des lits du service des urgences</div>
    <p class="card-subtitle">
        Tous les lits du service des urgences avec leur état actuel.
    </p>

    <table class="table">
        <tr>
            <th>Numéro</th>
            <th>État</th>
            <th>Service médical</th>
        </tr>

        <?php foreach (($lits ?? []) as $lit): ?>
            <tr>
                <td><?= (int) $lit['numeroLit'] ?></td>
                <td><?= htmlspecialchars($lit['etatLit']) ?></td>
                <td><?= htmlspecialchars($lit['serviceNom'] ?? 'Non défini') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>