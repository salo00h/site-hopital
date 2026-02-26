<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<h1 class="page-title">Accueil Infirmier d’accueil</h1>

<div class="dashboard-grid">
  <div class="card">
    <div class="card-title">Dossiers</div>
    <p class="card-subtitle">Rechercher / Consulter</p>
    <a class="btn" href="index.php?action=dossiers_list">Ouvrir</a>
  </div>

  <div class="card">
    <div class="card-title">Nouveau patient</div>
    <p class="card-subtitle">Créer dossier patient</p>
    <a class="btn btn-primary" href="index.php?action=dossier_create_form">Créer</a>
  </div>

  <div class="card">
    <div class="card-title">Dashboard lits</div>
    <p class="card-subtitle">Voir état des lits</p>
    <a class="btn" href="index.php?action=lits_dashboard">Ouvrir</a>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>