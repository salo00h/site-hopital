<?php
require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

$idDossier = (int)($_GET['idDossier'] ?? 0);
?>

<h1 class="page-title">Demander un transfert</h1>

<div class="card">

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
      <?= htmlspecialchars((string)$_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars((string)$_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <p>
    <strong>Dossier :</strong>
    #<?= (int)$idDossier ?> —
    <?= htmlspecialchars(($dossier['nom'] ?? '') . ' ' . ($dossier['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
  </p>

  <form class="form" method="post" action="index.php?action=transfert_create">
    <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

    <p>
      <label for="hopitalDestinataire">Hôpital destinataire *</label>
      <select id="hopitalDestinataire" name="hopitalDestinataire" required>
        <option value="">-- Choisir un hôpital --</option>

        <?php foreach (($hopitaux ?? []) as $h): ?>
          <option value="<?= htmlspecialchars((string)$h['nom'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$h['nom'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars((string)$h['ville'], ENT_QUOTES, 'UTF-8') ?>)
          </option>
        <?php endforeach; ?>

      </select>
    </p>

    <p>
      <label for="serviceDestinataire">Service destinataire (optionnel)</label>
      <input id="serviceDestinataire" type="text" name="serviceDestinataire">
    </p>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Envoyer</button>
      <a class="btn btn-link" href="index.php?action=dossier_detail_medecin&id=<?= (int)$idDossier ?>">Annuler</a>
    </div>
  </form>

  <div class="separator"></div>

  <h2 class="card-title">Historique transferts (patient)</h2>

  <?php if (empty($historique ?? [])): ?>
    <p>Aucun transfert.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date création</th>
          <th>Statut</th>
          <th>Hôpital destinataire</th>
          <th>Service</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($historique ?? []) as $t): ?>
          <tr>
            <td><?= (int)($t['idTransfer'] ?? 0) ?></td>
            <td><?= htmlspecialchars((string)($t['dateCreation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($t['statutTransfer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($t['hopitalDestinataire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($t['serviceDestinataire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>