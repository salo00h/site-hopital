<?php
declare(strict_types=1);

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

$dossier = $dossier ?? [];
$idDossier = (int)($dossier['idDossier'] ?? 0);

$equipementsReserves = $equipementsReserves ?? [];
$examens = $examens ?? [];
$transferts = $transferts ?? [];

// Helper XSS
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Flash messages (session)
$flashSuccess = '';
$flashError = '';

if (!empty($_SESSION['flash_success'])) {
    $flashSuccess = (string)$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    $flashError = (string)$_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>

<h1 class="page-title">Dossier patient - Médecin</h1>

<?php if ($flashSuccess !== ''): ?>
  <div class="alert alert-success"><?= $h($flashSuccess) ?></div>
<?php endif; ?>

<?php if ($flashError !== ''): ?>
  <div class="alert alert-danger"><?= $h($flashError) ?></div>
<?php endif; ?>

<!-- =========================
     INFOS DOSSIER
========================= -->
<div class="card">
  <h2 class="card-title">Informations</h2>

  <table class="table">
    <tbody>
      <tr>
        <th style="width:220px;">Nom</th>
        <td><?= $h($dossier['nom'] ?? '') ?></td>
      </tr>
      <tr>
        <th>Prénom</th>
        <td><?= $h($dossier['prenom'] ?? '') ?></td>
      </tr>
      <tr>
        <th>ID Patient</th>
        <td><?= $h($dossier['idPatient'] ?? '') ?></td>
      </tr>
      <tr>
        <th>Motif</th>
        <td><?= $h($dossier['motifAdmission'] ?? '') ?></td>
      </tr>
      <tr>
        <th>Date admission</th>
        <td><?= $h($dossier['dateAdmission'] ?? '') ?></td>
      </tr>
      <tr>
        <th>Lit attribué</th>
        <td><?= $h($dossier['numeroLit'] ?? 'Non attribué') ?></td>
      </tr>
    </tbody>
  </table>

  <div class="actions">
    <a class="btn" href="index.php?action=dossiers_list">← Retour liste</a>

    <a class="btn btn-primary" href="index.php?action=dossier_edit_form&id=<?= $idDossier ?>">
      Modifier dossier
    </a>

    <a class="btn" href="index.php?action=examen_form&idDossier=<?= $idDossier ?>">
      Demander examen
    </a>

    <a class="btn" href="index.php?action=transfert_form&idDossier=<?= $idDossier ?>">
      Demander transfert
    </a>

    <a class="btn" href="index.php?action=equipements_list_medecin&idDossier=<?= $idDossier ?>">
      Réserver équipement
    </a>
  </div>
</div>

<!-- =========================
     EQUIPEMENTS 
========================= -->
<div class="card">
  <h2 class="card-title">Équipements réservés</h2>

  <?php if (empty($equipementsReserves)): ?>
    <p>Aucun équipement réservé pour ce dossier.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Numéro</th>
          <th>Localisation</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($equipementsReserves as $eq): ?>
          <tr>
            <td><?= $h($eq['typeEquipement'] ?? '') ?></td>
            <td><?= (int)($eq['numeroEquipement'] ?? 0) ?></td>
            <td><?= !empty($eq['localisation']) ? $h($eq['localisation']) : '-' ?></td>
            <td><?= $h($eq['etatEquipement'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- =========================
     EXAMENS 
========================= -->
<div class="card">
  <h2 class="card-title">Demandes d'examens</h2>

  <?php if (empty($examens)): ?>
    <p>Aucune demande d'examen pour ce dossier.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Type</th>
          <th>Note</th>
          <th>Date</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($examens as $e): ?>
          <tr>
            <td><?= (int)($e['idExamen'] ?? 0) ?></td>
            <td><?= $h($e['typeExamen'] ?? '') ?></td>
            <td><?= $h($e['noteMedecin'] ?? '') ?></td>
            <td><?= $h($e['dateDemande'] ?? '') ?></td>
            <td><?= $h($e['statut'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- =========================
     TRANSFERTS 
========================= -->
<div class="card">
  <h2 class="card-title">Demandes de transfert</h2>

  <?php if (empty($transferts)): ?>
    <p>Aucune demande de transfert pour ce patient.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date création</th>
          <th>Statut</th>
          <th>Hôpital destinataire</th>
          <th>Service</th>
          <th>Date transfert</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transferts as $t): ?>
          <tr>
            <td><?= (int)($t['idTransfer'] ?? 0) ?></td>
            <td><?= $h($t['dateCreation'] ?? '') ?></td>
            <td><?= $h($t['statutTransfer'] ?? '') ?></td>
            <td><?= $h($t['hopitalDestinataire'] ?? '') ?></td>
            <td><?= $h($t['serviceDestinataire'] ?? '-') ?></td>
            <td><?= $h($t['dateTransfer'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>