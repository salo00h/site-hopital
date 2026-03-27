<?php
/*
|--------------------------------------------------------------------------
| VIEW : Tableau de bord technique
|--------------------------------------------------------------------------
| Cette vue affiche :
| - les messages d’alerte récents
| - les statistiques des lits
| - les statistiques des équipements
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
?>

<section class="tech-board">
    <h1 class="page-title tech-board-title">Tableau de bord</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="tech-panel">
        <h2 class="tech-panel-title">Message alert</h2>

        <?php
        /*
        --------------------------------------------------------------------
        Affichage des alertes :
        - les lignes deviennent cliquables si un lien est présent
        - les alertes du jour sont marquées "Nouveau"
        - cette préparation sert seulement à garder l'affichage clair
        --------------------------------------------------------------------
        */
        ?>
        <div class="tech-alerts-box">
            <?php if (empty($alertesRecentes)): ?>
                <div class="tech-alert-row">
                    <span>Aucune alerte récente</span>
                    <span class="tech-alert-time">—</span>
                </div>
            <?php else: ?>
                <?php foreach ($alertesRecentes as $a): ?>
                    <?php
                    $type         = (string) ($a['typeAlerte'] ?? '');
                    $message      = (string) ($a['message'] ?? '');
                    $dateCreation = (string) ($a['dateCreation'] ?? '');
                    $link         = (string) ($a['action'] ?? '');

                    $isClickable = str_starts_with($link, 'index.php?action=');
                    $isNew       = ($dateCreation === date('Y-m-d'));

                    $rowClass = 'tech-alert-row';

                    if ($type === 'panne_Lit' || $type === 'panne_Equipement') {
                        $rowClass .= ' is-danger';
                    } elseif ($type === 'demande_transfert') {
                        $rowClass .= ' is-info';
                    }

                    if ($isNew) {
                        $rowClass .= ' is-new';
                    }
                    ?>

                    <?php if ($isClickable): ?>
                        <a
                            href="<?= htmlspecialchars($link) ?>"
                            class="<?= htmlspecialchars($rowClass) ?>"
                        >
                            <span class="tech-alert-text">
                                <?= htmlspecialchars($type) ?> - <?= htmlspecialchars($message) ?>
                                <?php if ($isNew): ?>
                                    <span class="tech-alert-badge">Nouveau</span>
                                <?php endif; ?>
                            </span>
                            <span class="tech-alert-time"><?= htmlspecialchars($dateCreation) ?></span>
                        </a>
                    <?php else: ?>
                        <div class="<?= htmlspecialchars($rowClass) ?>">
                            <span class="tech-alert-text">
                                <?= htmlspecialchars($type) ?> - <?= htmlspecialchars($message) ?>
                                <?php if ($isNew): ?>
                                    <span class="tech-alert-badge">Nouveau</span>
                                <?php endif; ?>
                            </span>
                            <span class="tech-alert-time"><?= htmlspecialchars($dateCreation) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php
        /*
        --------------------------------------------------------------------
        Dashboard en deux colonnes :
        - colonne gauche : gestion des lits
        - colonne droite : gestion des équipements
        Chaque bloc contient une grille 2x2.
        La vue reste centrée sur la présentation uniquement.
        --------------------------------------------------------------------
        */
        ?>
        <div class="tech-dashboard-split">

            <!-- ===== LITS ===== -->
            <div>
                <h3 class="tech-section-title">Gestion des lits</h3>

                <div class="tech-grid-2x2">
                    <div class="tech-stat-card">
                        <span class="mini-tag">LIT</span>
                        <div class="tech-stat-label">Lit Disponible</div>
                        <div class="tech-stat-value"><?= (int) $statsLits['disponible'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">LIT</span>
                        <div class="tech-stat-label">Lits en panne</div>
                        <div class="tech-stat-value"><?= (int) $statsLits['panne'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">LIT</span>
                        <div class="tech-stat-label">Lits HS</div>
                        <div class="tech-stat-value"><?= (int) $statsLits['hs'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">LIT</span>
                        <div class="tech-stat-label">Lits en maintenance</div>
                        <div class="tech-stat-value"><?= (int) $statsLits['maintenance'] ?></div>
                    </div>
                </div>
            </div>

            <!-- ===== EQUIPEMENTS ===== -->
            <div>
                <h3 class="tech-section-title">Gestion des équipements</h3>

                <div class="tech-grid-2x2">
                    <div class="tech-stat-card">
                        <span class="mini-tag">EQP</span>
                        <div class="tech-stat-label">Équipements Disponible</div>
                        <div class="tech-stat-value"><?= (int) $statsEquipements['disponible'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">EQP</span>
                        <div class="tech-stat-label">Équipements en panne</div>
                        <div class="tech-stat-value"><?= (int) $statsEquipements['panne'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">EQP</span>
                        <div class="tech-stat-label">Équipements HS</div>
                        <div class="tech-stat-value"><?= (int) $statsEquipements['hs'] ?></div>
                    </div>

                    <div class="tech-stat-card">
                        <span class="mini-tag">EQP</span>
                        <div class="tech-stat-label">Équipements en maintenance</div>
                        <div class="tech-stat-value"><?= (int) $statsEquipements['maintenance'] ?></div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>