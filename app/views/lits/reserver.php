<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<h1 class="page-title">Réserver un lit</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($availableLits)): ?>
  <div class="card">
    <p>Aucun lit disponible pour le moment.</p>
    <a class="btn" href="index.php?action=lits_dashboard">Retour au dashboard lits</a>
  </div>
<?php else: ?>
  <div class="card">
    <form class="form" method="post" action="index.php?action=lit_reserver">
      <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

      <p>
        <label>Lit disponible :</label>
        <select name="idLit" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($availableLits as $lit): ?>
            <option value="<?= (int)$lit['idLit'] ?>">Lit #<?= (int)$lit['numeroLit'] ?></option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label>Date début :</label>
        <input type="datetime-local" name="dateDebut" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" required>
      </p>

      <p>
        <label>Date fin :</label>
        <input type="datetime-local" name="dateFin" value="<?= htmlspecialchars(date('Y-m-d\TH:i', time() + 2*3600)) ?>" required>
      </p>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Réserver</button>
        <a class="btn" href="index.php?action=dossier_detail&id=<?= (int)$idDossier ?>">Annuler</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>