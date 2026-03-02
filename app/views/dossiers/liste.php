<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<?php /** @var array $dossiers */ ?>

<?php
// Détection du rôle : affichage des équipements uniquement pour le médecin
$isMedecin = (($_SESSION['user']['role'] ?? '') === 'MEDECIN');
?>

<h1 class="page-title">Liste des dossiers</h1>

<form class="actions" method="get" action="index.php">
  <input type="hidden" name="action" value="dossiers_list">

  <input type="text" name="q"
         placeholder="Rechercher (nom/prénom/id)"
         value="<?= htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <button class="btn btn-primary" type="submit">Rechercher</button>

  <?php if (!$isMedecin): ?>
    <a class="btn" href="index.php?action=dossier_create_form">+ Nouveau dossier</a>
  <?php endif; ?>
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

        <?php if ($isMedecin): ?>
          <th>Équipements</th>
        <?php endif; ?>

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
            <?= !empty($d['numeroLit'])
                ? htmlspecialchars((string)$d['numeroLit'], ENT_QUOTES, 'UTF-8')
                : '-' ?>
          </td>

          <?php if ($isMedecin): ?>
            <td>
              <?php
                $id = (int)$d['idDossier'];
                // Résumé des équipements liés au dossier (ex: ECG x2)
                echo htmlspecialchars($equipementsResume[$id] ?? '-', ENT_QUOTES, 'UTF-8');
              ?>
            </td>
          <?php endif; ?>

          <td><?= htmlspecialchars((string)$d['statut'], ENT_QUOTES, 'UTF-8') ?></td>

          <td>
            <?php
              $role = $_SESSION['user']['role'] ?? '';
              $action = ($role === 'MEDECIN')
                  ? 'dossier_detail_medecin'
                  : 'dossier_detail';
            ?>
            <a class="btn"
               href="index.php?action=<?= $action ?>&id=<?= (int)$d['idDossier'] ?>">
               Consulter
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>