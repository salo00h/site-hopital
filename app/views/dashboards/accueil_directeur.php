<?php
/*
|--------------------------------------------------------------------------
| VUE : Dashboard Directeur
|--------------------------------------------------------------------------
| Cette page affiche :
| - les statistiques des lits
| - les statistiques des équipements
| - les alertes récentes
| - un petit historique des transferts
|
| Remarque :
| Les données doivent être préparées dans
| DashboardController.php avant d’ouvrir cette vue.
|
| Organisation de la vue :
| - ce fichier contient uniquement l'affichage
| - ce fichier doit rester simple et lisible
| - pas de logique métier ici
| - pas de CSS ici
| - uniquement affichage et structure claire
|--------------------------------------------------------------------------
*/
?>

<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<section class="tech-board">

    <!-- Titre principal -->
    <h1 class="page-title" style="text-align:center; margin-bottom:20px;">
        Tableau de bord
    </h1>

    <!-- Message de succès -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Message d’erreur -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Grille principale -->
    <div class="mockup-grid">

        <!-- Colonne gauche -->
        <div>

            <!-- Bloc des lits -->
            <div class="mockup-row">

                <!-- Nombre de lits disponibles -->
                <div class="mockup-kpi">
                    <div class="mockup-kpi-title">Lit Disponible</div>
                    <div class="mockup-kpi-box">
                        <?= (int) ($litsDisponibles ?? 0) ?>/<?= (int) (($litsDisponibles ?? 0) + ($litsOccupes ?? 0)) ?>
                    </div>
                </div>

                <!-- Nombre de lits occupés -->
                <div class="mockup-kpi">
                    <div class="mockup-kpi-title">Lit Occupé</div>
                    <div class="mockup-kpi-box">
                        <?= (int) ($litsOccupes ?? 0) ?>/<?= (int) (($litsDisponibles ?? 0) + ($litsOccupes ?? 0)) ?>
                    </div>
                </div>

            </div>

            <!-- Bloc des équipements -->
            <div class="mockup-panel">
                <div class="mockup-panel-title">Équipement Disponible</div>

                <div class="mockup-mini-table">
                    <?php if (!empty($equipementsStats)): ?>
                        <?php foreach ($equipementsStats as $equipement): ?>
                            <div class="mockup-mini-row">
                                <span>
                                    <?= htmlspecialchars((string) ($equipement['type'] ?? 'Équipement'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <strong>
                                    <?= (int) ($equipement['disponibles'] ?? 0) ?>/<?= (int) ($equipement['total'] ?? 0) ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mockup-mini-row">
                            <span>Aucun équipement</span>
                            <strong>0/0</strong>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Taux d’occupation -->
                <div style="margin-top:18px;">
                    <div class="mockup-occupation-title">taux d’occupation d’un service</div>

                    <div class="mockup-progress">
                        <div
                            class="mockup-progress-bar"
                            style="width: <?= (int) ($tauxOccupation ?? 0) ?>%;"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Historique des transferts -->
            <div class="mockup-panel">
                <div class="mockup-panel-title" style="text-align:center;">
                    historique de transferts
                </div>

                <div class="mockup-mini-table">
                    <?php if (!empty($historiqueTransferts)): ?>
                        <?php foreach ($historiqueTransferts as $transfert): ?>
                            <div class="mockup-mini-row">
                                <span>
                                    <?php
                                    // Préparer le texte du transfert pour l’affichage uniquement.
                                    $patientId  = (int) ($transfert['idPatient'] ?? 0);
                                    $hopitalDest = (string) ($transfert['hopitalDestinataire'] ?? '');
                                    $texte      = 'Patient ID-' . $patientId;

                                    if ($hopitalDest !== '') {
                                        $texte .= ' → ' . $hopitalDest;
                                    }
                                    ?>
                                    <?= htmlspecialchars($texte, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mockup-mini-row">
                            <span>Aucun transfert</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Colonne droite -->
        <div>
            <div class="mockup-panel">
                <div class="mockup-panel-title">Message alert</div>

                <?php if (!empty($alertesRecentes)): ?>
                    <?php foreach ($alertesRecentes as $alerte): ?>
                        <?php
                        // Texte principal de l’alerte pour l’affichage.
                        $message = (string) ($alerte['message'] ?? 'Alerte');

                        // Type d’alerte utilisé pour ajuster l’affichage du badge.
                        $typeAlerte = strtolower((string) ($alerte['typeAlerte'] ?? ''));

                        // Badge par défaut.
                        $badgeClass = 'mockup-badge is-warning';

                        // Adapter le badge si l’alerte semble critique.
                        if (
                            str_contains($typeAlerte, 'critique') ||
                            str_contains($typeAlerte, 'panne') ||
                            str_contains($message, 'HS')
                        ) {
                            $badgeClass = 'mockup-badge is-danger';
                        }

                        // Lien par défaut.
                        $lien = 'index.php?action=dashboard';

                        // Rediriger vers les transferts pour une alerte de transfert.
                        if (str_contains($typeAlerte, 'transfert')) {
                            $lien = 'index.php?action=transferts_traitement_directeur';
                        }
                        ?>

                        <div class="mockup-alert-line" style="margin-bottom:8px;">
                            <a
                                href="<?= htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') ?>"
                                style="text-decoration:none; color:inherit;"
                            >
                                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <span class="<?= $badgeClass ?>">!</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mockup-alert-line">
                        <span>Aucune alerte</span>
                        <span class="mockup-badge">i</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>