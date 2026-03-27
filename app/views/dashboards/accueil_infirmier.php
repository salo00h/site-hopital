<?php
/*
|--------------------------------------------------------------------------
| VUE : Accueil Infirmier
|--------------------------------------------------------------------------
| Cette vue affiche :
| - les statistiques principales
| - les équipements disponibles
| - les messages d’alerte
| - le taux d’occupation
|
| Organisation de la vue :
| - ce fichier contient uniquement l'affichage
| - ce fichier doit rester simple et lisible
| - pas de logique métier ici
| - pas de CSS ici
| - uniquement affichage et structure claire
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Fonction locale d’échappement
|--------------------------------------------------------------------------
| Cette fonction sert uniquement à sécuriser l’affichage HTML.
| Elle garde la vue propre, lisible et centrée sur la présentation.
|--------------------------------------------------------------------------
*/
$h = static function (mixed $v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1 class="page-title">Accueil Infirmier</h1>

<div class="stats-grid stats-grid-3">

    <div class="stat-card">
        <div class="stat-label">Lit disponible</div>
        <div class="stat-value"><?= (int) ($stats['lits_disponibles'] ?? 0) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Lit occupé</div>
        <div class="stat-value"><?= (int) ($stats['lits_occupes'] ?? 0) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Patient en consultation</div>
        <div class="stat-value"><?= (int) ($stats['patients_consultation'] ?? 0) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">En attente consultation</div>
        <div class="stat-value"><?= (int) ($stats['patients_attente'] ?? 0) ?></div>
    </div>

</div>

<div class="dashboard-sections">

    <div class="card">
        <div class="card-title">Équipement disponible</div>

        <?php if (!empty($stats['equipements_stats'])): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Disponibles</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['equipements_stats'] as $eq): ?>
                        <tr>
                            <td><?= $h($eq['type'] ?? '') ?></td>
                            <td><?= (int) ($eq['disponibles'] ?? 0) ?></td>
                            <td><?= (int) ($eq['total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun équipement trouvé.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Messages d’alerte</div>

        <?php if (!empty($alertesRecentes)): ?>
            <div class="alerts-list">
                <?php foreach ($alertesRecentes as $alerte): ?>
                    <div class="alert-item">
                        <div class="alert-item-text">
                            <?= $h($alerte['message'] ?? 'Alerte') ?>
                        </div>
                        <div class="alert-item-meta">
                            <?= $h($alerte['typeAlerte'] ?? '') ?> — <?= $h($alerte['dateCreation'] ?? '') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucune alerte pour le moment.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Taux d’occupation d’un service</div>
        <p><?= (int) $tauxOccupation ?>%</p>

        <div class="progress">
            <div
                class="progress-bar"
                style="width: <?= (int) $tauxOccupation ?>%"
            ></div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>