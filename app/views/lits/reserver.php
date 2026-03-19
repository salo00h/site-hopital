<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<h1 class="page-title">Réserver un lit</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($availableLits)): ?>
  <div class="card">
    <p>Aucun lit disponible pour le moment.</p>
    <a class="btn" href="index.php?action=lits_dashboard">Retour au dashboard lits</a>
  </div>
<?php else: ?>
  <div class="card">
    <p class="card-subtitle">
      La réservation du lit se fait sur une période donnée.
      La date de début correspond au moment où le lit est attribué au patient.
      La date de fin correspond à la fin prévue d’occupation ou à la prochaine réévaluation.
    </p>

    <form class="form" method="post" action="index.php?action=lit_reserver">
      <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

      <p>
        <label>Lit disponible</label>
        <select name="idLit" required>
          <option value="">-- Choisir --</option>
          <?php foreach ($availableLits as $lit): ?>
            <option value="<?= (int)$lit['idLit'] ?>">Lit #<?= (int)$lit['numeroLit'] ?></option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label>Date début de réservation</label>
        <input type="datetime-local" name="dateDebut" value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
      </p>

      <p>
        <label>Date fin prévue</label>
        <input type="datetime-local" name="dateFin" value="<?= htmlspecialchars(date('Y-m-d\TH:i', time() + 2 * 3600), ENT_QUOTES, 'UTF-8') ?>" required>
      </p>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Réserver</button>
        <a class="btn" href="index.php?action=dossier_detail&id=<?= (int)$idDossier ?>">Annuler</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>