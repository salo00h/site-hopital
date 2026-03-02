<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<?php /** @var array $dossiers */ ?>

<h1 class="page-title">Liste des dossiers</h1>

<form class="actions" method="get" action="index.php">
  <input type="hidden" name="action" value="dossiers_list">

  <input type="text" name="q"
         placeholder="Rechercher (nom/prénom/id)"
         value="<?= htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <button class="btn btn-primary" type="submit">Rechercher</button>
  <a class="btn" href="index.php?action=dossier_create_form">+ Nouveau dossier</a>
</form>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>ID Dossier</th>
        <th>Patient</th>
        <th>Date naissance</th>
        <th>Genre</th>
        <th>Date admission</th>
        <th>Lit</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($dossiers as $d): ?>
        <tr>
          <td><?= (int)$d['idDossier'] ?></td>
          <td><?= htmlspecialchars($d['prenom'].' '.$d['nom'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$d['dateNaissance'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$d['genre'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string)$d['dateAdmission'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if (!empty($d['numeroLit'])): ?>
              <?= htmlspecialchars((string)$d['numeroLit'], ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
              -
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string)$d['statut'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a class="btn" href="index.php?action=dossier_detail&id=<?= (int)$d['idDossier'] ?>">Consulter</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>