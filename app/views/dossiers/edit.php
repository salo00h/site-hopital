<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<h1 class="page-title">Modifier dossier #<?= (int)$dossier['idDossier']; ?></h1>

<?php if (!empty($error)) : ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card">
  <form class="form" method="post" action="index.php?action=dossier_update">
    <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier']; ?>">
    <input type="hidden" name="idPatient" value="<?= (int)$dossier['idPatient']; ?>">

    <h3>Patient</h3>

    <p>
      <label>Nom</label>
      <input name="nom" value="<?= htmlspecialchars($dossier['nom'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
      <label>Prénom</label>
      <input name="prenom" value="<?= htmlspecialchars($dossier['prenom'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
      <label>Date naissance</label>
      <input type="date" name="dateNaissance" value="<?= htmlspecialchars((string)$dossier['dateNaissance'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </p>

    <p>
      <label>Genre</label>
      <select name="genre">
        <option value="Homme" <?= ($dossier['genre']==='Homme') ? 'selected' : '' ?>>Homme</option>
        <option value="Femme" <?= ($dossier['genre']==='Femme') ? 'selected' : '' ?>>Femme</option>
        <option value="Autre" <?= ($dossier['genre']==='Autre') ? 'selected' : '' ?>>Autre</option>
      </select>
    </p>

    <p>
      <label>Téléphone</label>
      <input name="telephone" value="<?= htmlspecialchars((string)$dossier['telephone'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <div class="separator"></div>

    <h3>Dossier</h3>

    <p>
      <label>Date admission</label>
      <input type="date" name="dateAdmission" value="<?= htmlspecialchars((string)$dossier['dateAdmission'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <p>
      <label>Statut</label>
      <input name="statut" value="<?= htmlspecialchars((string)$dossier['statut'], ENT_QUOTES, 'UTF-8'); ?>">
    </p>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Enregistrer</button>
      <a class="btn" href="index.php?action=dossier_detail&id=<?= (int)$dossier['idDossier']; ?>">Annuler</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>