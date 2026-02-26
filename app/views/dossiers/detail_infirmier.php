<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<?php /** @var array $dossier */ ?>

<h1 class="page-title">Dossier #<?= (int)$dossier['idDossier'] ?></h1>
<?php if (!empty($_SESSION['success'])): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['success']); ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="card">
  <div class="card-title">Patient</div>
  <ul>
    <li>Nom: <?= htmlspecialchars($dossier['nom'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>Prénom: <?= htmlspecialchars($dossier['prenom'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>Date naissance: <?= htmlspecialchars((string)$dossier['dateNaissance'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>Genre: <?= htmlspecialchars((string)$dossier['genre'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>Téléphone: <?= htmlspecialchars((string)$dossier['telephone'], ENT_QUOTES, 'UTF-8') ?></li>
  </ul>
</div>

<div class="card">
  <div class="card-title">Dossier</div>
  <ul>
    <li>Date admission: <?= htmlspecialchars((string)$dossier['dateAdmission'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>Statut: <?= htmlspecialchars((string)$dossier['statut'], ENT_QUOTES, 'UTF-8') ?></li>
    <li>
      Lit:
      <?php if (!empty($dossier['numeroLit'])): ?>
        n° <?= htmlspecialchars((string)$dossier['numeroLit'], ENT_QUOTES, 'UTF-8') ?>
      <?php else: ?>
        Aucun
      <?php endif; ?>
    </li>
    <li>Historique médical: <?= nl2br(htmlspecialchars((string)$dossier['historiqueMedical'], ENT_QUOTES, 'UTF-8')) ?></li>
    <li>Antécédant: <?= nl2br(htmlspecialchars((string)$dossier['antecedant'], ENT_QUOTES, 'UTF-8')) ?></li>
  </ul>

  <div class="actions">
    <a class="btn" href="index.php?action=dossiers_list">← Retour liste</a>

    <a class="btn" href="index.php?action=dossier_edit_form&id=<?= (int)$dossier['idDossier']; ?>">
      Modifier
    </a>

    <?php if (empty($dossier['idLit'])): ?>
      <a class="btn btn-primary" href="index.php?action=lit_reserver_form&idDossier=<?= (int)$dossier['idDossier'] ?>">
        Réserver un lit
      </a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>