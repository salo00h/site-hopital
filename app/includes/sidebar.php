<?php
$role = $_SESSION['user']['role'] ?? '';
?>
<aside class="sidebar">
  <div class="menu-box">
    <h4 class="menu-title">Menu</h4>

    <ul class="menu">
      <li><a href="index.php?action=dashboard">Dashboard</a></li>

      <?php if ($role === 'INFIRMIER_ACCUEIL'): ?>
        <li><a href="index.php?action=dossiers_list">Dossiers patients</a></li>
        <li><a href="index.php?action=dossier_create_form">Créer dossier</a></li>
        <li><a href="index.php?action=lits_dashboard">Tableau de bord des lits</a></li>
      <?php endif; ?>

      <?php if ($role === 'DIRECTEUR'): ?>
        <li><a href="index.php?action=transferts_historique">Transferts - Historique</a></li>
        <li><a href="index.php?action=transferts_traitement">Traiter transferts</a></li>
      <?php endif; ?>

      <?php if ($role === 'TECHNICIEN'): ?>
        <li><a href="index.php?action=lits_list">Gestion lits</a></li>
        <li><a href="index.php?action=equipements_list">Gestion équipements</a></li>
      <?php endif; ?>
    </ul>
  </div>
</aside>

<main class="main">