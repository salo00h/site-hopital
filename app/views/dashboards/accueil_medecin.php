<?php
/*
  ==============================
  VIEW : Accueil Médecin
  ==============================
  Dashboard simple du médecin.

  Cette vue affiche :
  - la recherche rapide d'un dossier patient
  - les derniers dossiers
  - les consultations à venir
  - les dossiers en attente d'examen
  - les équipements disponibles
  - les lits disponibles / occupés
  - les alertes récentes
  - le taux d'occupation

  Remarque :
  - les données doivent être préparées par le contrôleur
  - cette vue reste simple et sans logique métier complexe
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Sécurisation des variables
|--------------------------------------------------------------------------
| On initialise des valeurs par défaut pour éviter les warnings
| si une donnée manque dans le contrôleur.
*/
$dossiersRecent           = $dossiersRecent ?? [];
$consultations            = $consultations ?? [];
$equipementsStats         = $equipementsStats ?? [];
$alertes                  = $alertes ?? [];
$examensEnAttente         = $examensEnAttente ?? [];
$nbExamensEnAttente       = $nbExamensEnAttente ?? 0;
$nbEquipementsDisponibles = $nbEquipementsDisponibles ?? 0;
$litsDisponibles          = $litsDisponibles ?? 0;
$litsOccupes              = $litsOccupes ?? 0;
$tauxOccupation           = $tauxOccupation ?? 0;

/*
|--------------------------------------------------------------------------
| Sécurisation du taux d'occupation
|--------------------------------------------------------------------------
*/
$tauxOccupation = (int) $tauxOccupation;

if ($tauxOccupation < 0) {
    $tauxOccupation = 0;
}

if ($tauxOccupation > 100) {
    $tauxOccupation = 100;
}

/*
|--------------------------------------------------------------------------
| Tri simple des consultations par priorité décroissante
|--------------------------------------------------------------------------
*/
if (!empty($consultations)) {
    usort($consultations, function ($a, $b) {
        return ((int) ($b['priorite'] ?? 0)) <=> ((int) ($a['priorite'] ?? 0));
    });
}
?>

<h1 class="page-title">Accueil Médecin</h1>

<div class="mockup-grid">

    <!-- Colonne gauche -->
    <div>

        <!-- Barre de recherche principale -->
        <div class="doctor-search-bar">
            <form method="GET" action="index.php" class="doctor-search-form">
                <input type="hidden" name="action" value="dossiers_list">
                <input
                    type="text"
                    name="q"
                    class="doctor-search-input"
                    placeholder="Rechercher dossier"
                >
                <button type="submit" class="doctor-search-btn">Rechercher</button>
            </form>
        </div>

        <!-- Dossiers patients -->
        <div class="mockup-panel compact">
            <div class="mockup-panel-title">Dossiers patients</div>
            <p class="card-subtitle">Rechercher / Consulter</p>
            <a class="btn" href="index.php?action=dossiers_list">Ouvrir</a>
        </div>

        <!-- Derniers dossiers -->
        <div class="mockup-panel compact">
            <div class="mockup-panel-title">Derniers dossiers</div>
            <p class="card-subtitle">Accès rapide (<?= count($dossiersRecent) ?>)</p>

            <?php if (!empty($dossiersRecent)): ?>
                <ul>
                    <?php foreach ($dossiersRecent as $d): ?>
                        <?php
                        $idDossier = (int) ($d['idDossier'] ?? 0);
                        $nomComplet = trim((string) ($d['nomComplet'] ?? ''));
                        $label = $nomComplet !== '' ? $nomComplet : 'Dossier #' . $idDossier;
                        ?>
                        <li>
                            <a href="index.php?action=dossier_detail_medecin&id=<?= $idDossier ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucun dossier récent.</p>
            <?php endif; ?>
        </div>

        <!-- Liste des consultations -->
        <div class="mockup-panel">
            <div class="mockup-panel-title">
                Liste des consultations à venir (<?= count($consultations) ?>)
            </div>

            <?php if (!empty($consultations)): ?>
                <div class="mockup-mini-table">
                    <?php foreach ($consultations as $c): ?>
                        <?php
                        $nomPatient = trim((string) (($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')));
                        $idDossierConsultation = (int) ($c['idDossier'] ?? 0);
                        ?>
                        <div class="mockup-mini-row">
                            <span>
                                <a href="index.php?action=dossier_detail_medecin&id=<?= $idDossierConsultation ?>">
                                    <?= htmlspecialchars($nomPatient, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </span>
                            <span>
                                P<?= (int) ($c['priorite'] ?? 0) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucune consultation à venir.</p>
            <?php endif; ?>
        </div>

        <!-- Dossiers en attente d'examen -->
        <div class="mockup-panel">
            <div class="mockup-panel-title">Dossiers en attente d'examen</div>

            <p><b><?= (int) $nbExamensEnAttente ?></b></p>

            <?php if (!empty($examensEnAttente)): ?>
                <ul>
                    <?php foreach ($examensEnAttente as $e): ?>
                        <?php $nom = trim(($e['nom'] ?? '') . ' ' . ($e['prenom'] ?? '')); ?>
                        <li>
                            <a href="index.php?action=dossier_detail_medecin&id=<?= (int) ($e['idDossier'] ?? 0) ?>">
                                <?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            - <?= htmlspecialchars((string) ($e['typeExamen'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucun dossier en attente.</p>
            <?php endif; ?>
        </div>

        <!-- KPI -->
        <div class="mockup-row">

            <div class="mockup-kpi">
                <div class="mockup-kpi-title">Lit disponible</div>
                <div class="mockup-kpi-box"><?= (int) $litsDisponibles ?></div>
            </div>

            <div class="mockup-kpi">
                <div class="mockup-kpi-title">Lit occupé</div>
                <div class="mockup-kpi-box"><?= (int) $litsOccupes ?></div>
            </div>

            <div class="mockup-kpi">
                <div class="mockup-kpi-title">En attente examen</div>
                <div class="mockup-kpi-box"><?= (int) $nbExamensEnAttente ?></div>
            </div>

            <div class="mockup-kpi">
                <div class="mockup-kpi-title">Équipements dispo</div>
                <div class="mockup-kpi-box"><?= (int) $nbEquipementsDisponibles ?></div>
            </div>

        </div>

        <!-- Taux d'occupation -->
        <div class="mockup-occupation">
            <div class="mockup-occupation-title">Taux d’occupation</div>
            <div class="mockup-progress">
                <div class="mockup-progress-bar" style="width: <?= $tauxOccupation ?>%"></div>
            </div>
        </div>

    </div>

    <!-- Colonne droite -->
    <div>

        <!-- Equipements -->
        <div class="mockup-panel">
            <div class="mockup-panel-title">Équipement disponible</div>

            <?php if (!empty($equipementsStats)): ?>
                <?php foreach ($equipementsStats as $row): ?>
                    <div class="mockup-mini-row">
                        <span><?= htmlspecialchars((string) ($row['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= (int) ($row['disponibles'] ?? 0) ?>/<?= (int) ($row['total'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Pas de données équipements.</p>
            <?php endif; ?>
        </div>

        <!-- Alertes -->
        <div class="mockup-panel">
            <div class="mockup-panel-title">Message alert</div>

            <?php if (!empty($alertes)): ?>
                <?php foreach ($alertes as $a): ?>
                    <div class="mockup-alert-line">
                        <span><?= htmlspecialchars((string) ($a['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mockup-badge">!</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune alerte.</p>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>