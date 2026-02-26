<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<h1 class="page-title">Dashboard des lits (Service)</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($alertMessage)): ?>
  <div class="alert <?= ($alertLevel === 'danger') ? 'alert-danger' : 'alert-warning' ?>">
    <?= htmlspecialchars($alertMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Lits disponibles</div>
    <div class="stat-value"><?= (int)$nbDisponible ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Lits occupés</div>
    <div class="stat-value"><?= (int)$nbOccupe ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Lits réservés</div>
    <div class="stat-value"><?= (int)$nbReserve ?></div>
  </div>

  <div class="stat-card">
    <div class="stat-label">Taux d’occupation</div>
    <div class="stat-value"><?= (int)$tauxOccupation ?>%</div>
    <div class="progress">
      <div class="progress-bar" style="width: <?= (int)$tauxOccupation ?>%"></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">Liste des lits</div>

  <table class="table">
    <tr>
      <th>Numéro</th>
      <th>État</th>
    </tr>
    <?php foreach (($lits ?? []) as $lit): ?>
      <tr>
        <td><?= (int)$lit['numeroLit'] ?></td>
        <td><?= htmlspecialchars($lit['etatLit']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
