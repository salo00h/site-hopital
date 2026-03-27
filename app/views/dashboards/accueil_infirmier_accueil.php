<?php
/*
|--------------------------------------------------------------------------
| VIEW : Accueil Infirmier d’accueil
|--------------------------------------------------------------------------
| Tableau de bord avec statistiques réelles.
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

/** @var array $stats */
/** @var array $alertesRecentes */
?>

<h1 class="page-title">Accueil Infirmier d’accueil</h1>

<div class="stats-grid stats-grid-3">

    <div class="stat-card">
        <div class="stat-label">Lits disponibles</div>
        <div class="stat-value"><?= (int) $stats['lits_disponibles'] ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Lits réservés</div>
        <div class="stat-value"><?= (int) $stats['lits_reserves'] ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Patients en consultation</div>
        <div class="stat-value"><?= (int) $stats['patients_consultation'] ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">En attente de consultation</div>
        <div class="stat-value"><?= (int) $stats['patients_attente'] ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Équipements disponibles</div>
        <div class="stat-value"><?= (int) $stats['equipements_disponibles'] ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Alertes</div>
        <div class="stat-value"><?= (int) $stats['alertes_total'] ?></div>
    </div>

</div>

<div class="dashboard-sections">

    <div class="card">
        <div class="card-title">Messages d’alerte</div>

        <?php if (!empty($alertesRecentes)): ?>
            <div class="alerts-list">
                <?php foreach ($alertesRecentes as $alerte): ?>
                    <div class="alert-item">
                        <div class="alert-item-text">
                            <?= htmlspecialchars((string) ($alerte['message'] ?? 'Alerte')) ?>
                        </div>
                        <div class="alert-item-meta">
                            <?= htmlspecialchars((string) ($alerte['typeAlerte'] ?? '')) ?>
                            —
                            <?= htmlspecialchars((string) ($alerte['dateCreation'] ?? '')) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="card-subtitle">Aucune alerte pour le moment.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">Niveaux de priorité</div>

        <div class="priority-grid">

            <div class="priority-box priority-high">
                <div class="priority-label">Niveau 1</div>
                <div class="priority-value"><?= (int) $stats['niveau_1'] ?></div>
            </div>

            <div class="priority-box priority-medium">
                <div class="priority-label">Niveau 2</div>
                <div class="priority-value"><?= (int) $stats['niveau_2'] ?></div>
            </div>

            <div class="priority-box priority-low">
                <div class="priority-label">Niveau 3</div>
                <div class="priority-value"><?= (int) $stats['niveau_3'] ?></div>
            </div>

        </div>
    </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>