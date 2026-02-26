<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<h1 class="page-title">Créer un dossier patient</h1>

<?php if (!empty($error)) : ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
  <form class="form" method="post" action="index.php?action=dossier_create">

    <h3>Infos patient</h3>

    <p>
      <label>Nom *</label>
      <input type="text" name="nom" required>
    </p>

    <p>
      <label>Prénom *</label>
      <input type="text" name="prenom" required>
    </p>

    <p>
      <label>Date naissance *</label>
      <input type="date" name="dateNaissance" required>
    </p>

    <p>
      <label>Genre *</label>
      <select name="genre" required>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
        <option value="Autre">Autre</option>
      </select>
    </p>

    <p>
      <label>Téléphone</label>
      <input type="text" name="telephone">
    </p>

    <div class="separator"></div>

    <h3>Infos dossier</h3>

    <p>
      <label>Date admission</label>
      <input type="date" name="dateAdmission" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
    </p>

    <p>
      <label>Statut *</label>
      <input type="text" name="statut" value="ouvert" required>
    </p>

    <p>
      <label>Niveau *</label>
      <select name="niveau" required>
        <option value="1">1</option><option value="2">2</option><option value="3">3</option>
        <option value="4">4</option><option value="5">5</option>
      </select>
    </p>

    <p>
      <label>Délai prise en charge *</label>
      <select name="delaiPriseCharge" required>
        <option value="0">0</option>
        <option value="10">10</option>
        <option value="30">30</option>
        <option value="NonImmediat">NonImmediat</option>
      </select>
    </p>

    <p>
      <label>État entrée</label>
      <input type="text" name="etat_entree">
    </p>

    <p>
      <label>Historique médical</label>
      <textarea name="historiqueMedical"></textarea>
    </p>

    <p>
      <label>Antécédant</label>
      <textarea name="antecedant"></textarea>
    </p>

    <!-- idHopital ثابت للتست -->
    <input type="hidden" name="idHopital" value="1">

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Créer</button>
      <a class="btn" href="index.php?action=dossiers_list">Annuler</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>