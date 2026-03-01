<?php
/*
  ==============================
  VIEW : Accueil Infirmier d’accueil
  ==============================
  Cette page affiche :
  - Accès à la liste des dossiers
  - Création d’un nouveau patient
  - Accès au dashboard des lits
  Aucune logique métier ici (seulement affichage).
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<h1 class="page-title">Accueil Infirmier d’accueil</h1>

<!-- Grille principale du tableau de bord -->
<div class="dashboard-grid">

  <!-- Carte : Liste des dossiers -->
  <div class="card">
    <div class="card-title">Dossiers</div>
    <p class="card-subtitle">Rechercher / Consulter</p>
    <!-- Redirection vers la liste -->
    <a class="btn" href="index.php?action=dossiers_list">Ouvrir</a>
  </div>

  <!-- Carte : Création nouveau patient -->
  <div class="card">
    <div class="card-title">Nouveau patient</div>
    <p class="card-subtitle">Créer dossier patient</p>
    <!-- Redirection vers le formulaire de création -->
    <a class="btn btn-primary" href="index.php?action=dossier_create_form">Créer</a>
  </div>

  <!-- Carte : Dashboard des lits -->
  <div class="card">
    <div class="card-title">Dashboard lits</div>
    <p class="card-subtitle">Voir état des lits</p>
    <!-- Redirection vers le tableau de bord des lits -->
    <a class="btn" href="index.php?action=lits_dashboard">Ouvrir</a>
  </div>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>