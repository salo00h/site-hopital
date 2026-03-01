<?php
/*
  ==============================
  VIEW : Accueil Médecin
  ==============================
  Page d'accueil du médecin.
  Affiche un mini dashboard (données déjà préparées par le contrôleur).
  Aucune logique métier ici.
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

// Sécuriser les variables (évite les warnings si un contrôleur oublie de les définir)
$dossiersRecent   = $dossiersRecent ?? [];
$equipementsStats = $equipementsStats ?? [];
$litsStats        = $litsStats ?? [];
$alertes          = $alertes ?? [];
?>

<h1 class="page-title">Accueil Médecin</h1>

<div class="dashboard-grid">

  <!-- Carte : Dossiers -->
  <div class="card">
    <div class="card-title">Dossiers patients</div>
    <p class="card-subtitle">Rechercher / Consulter</p>
    <a class="btn" href="index.php?action=dossiers_list">Ouvrir</a>
  </div>

  <!-- Carte : Derniers dossiers -->
  <div class="card">
    <div class="card-title">Derniers dossiers</div>
    <p class="card-subtitle">Accès rapide (<?= count($dossiersRecent) ?>)</p>

    <?php if ($dossiersRecent): ?>
      <ul>
        <?php foreach ($dossiersRecent as $d): ?>
          <?php
            $idDossier = (int)($d['idDossier'] ?? 0);
            $nom = trim((string)($d['nomComplet'] ?? ''));
            $label = $nom !== '' ? $nom : ('Dossier #' . $idDossier);
          ?>
          <li>
            <a href="index.php?action=dossier_detail_medecin&id=<?= $idDossier ?>">
              <?= htmlspecialchars($label) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Aucun dossier récent.</p>
    <?php endif; ?>
  </div>

  <!-- Carte : Équipements -->
  <div class="card">
    <div class="card-title">Équipements disponibles</div>
    <p class="card-subtitle">Résumé rapide</p>

    <?php if ($equipementsStats): ?>
      <ul>
        <?php foreach ($equipementsStats as $row): ?>
          <li>
            <?= htmlspecialchars((string)($row['type'] ?? '')) ?> :
            <?= (int)($row['disponibles'] ?? 0) ?>/<?= (int)($row['total'] ?? 0) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Pas de données équipements.</p>
    <?php endif; ?>

    <a class="btn" href="index.php?action=equipements_list_medecin">Voir détails</a>
  </div>

  <!-- Carte : Lits -->
  <div class="card">
    <div class="card-title">Lits</div>
    <p class="card-subtitle">Disponibles / Occupés</p>

    <p><b>Disponibles :</b> <?= (int)($litsStats['disponibles'] ?? 0) ?></p>
    <p><b>Occupés :</b> <?= (int)($litsStats['occupes'] ?? 0) ?></p>
  </div>

  <!-- Carte : Alertes -->
  <div class="card">
    <div class="card-title">Alertes</div>
    <p class="card-subtitle">Dernières alertes</p>

    <?php if ($alertes): ?>
      <ul>
        <?php foreach ($alertes as $a): ?>
          <li><?= htmlspecialchars((string)($a['message'] ?? '')) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Aucune alerte.</p>
    <?php endif; ?>
  </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>