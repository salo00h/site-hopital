<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<?php /** @var array $dossiers */ ?>

<?php
$role = $_SESSION['user']['role'] ?? '';
$isMedecin = in_array($role, ['MEDECIN', 'INFIRMIER'], true);
$isAccueil = ($role === 'INFIRMIER_ACCUEIL');

/*
|--------------------------------------------------------------------------
| Fonction helper pour sécuriser l'affichage
|--------------------------------------------------------------------------
*/
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1 class="page-title">Liste des dossiers</h1>

<form class="actions" method="get" action="index.php">
  <input type="hidden" name="action" value="dossiers_list">

  <input type="text" name="q"
         placeholder="Rechercher (nom/prénom/id)"
         value="<?= $h($q ?? '') ?>">

  <button class="btn btn-primary" type="submit">Rechercher</button>

  <?php if ($isAccueil): ?>
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
        <th>Date / heure arrivée</th>
        <th>Niveau</th>
        <th>Lit</th>

        <?php if ($isMedecin): ?>
          <th>Équipements</th>
          <th>Examens</th>
          <th>Transferts</th>
        <?php endif; ?>

        <th>Statut</th>
        <th></th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($dossiers as $d): ?>
        <tr>
          <td><?= (int)$d['idDossier'] ?></td>
          <td><?= $h(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?></td>
          <td><?= $h($d['dateNaissance'] ?? '') ?></td>
          <td><?= $h($d['genre'] ?? '') ?></td>
          <td><?= $h($d['dateAdmission'] ?? '') ?></td>
          <td><?= $h($d['niveau'] ?? '-') ?></td>
          <td>
            <?= !empty($d['numeroLit']) ? $h($d['numeroLit']) : '-' ?>
          </td>

          <?php if ($isMedecin): ?>
            <td>
              <?php
                $id = (int)$d['idDossier'];
                echo !empty($equipementsResume[$id]) ? 'Oui' : '-';
              ?>
            </td>

            <td>
              <?php
                $id = (int)$d['idDossier'];
                echo (int)($examensCount[$id] ?? 0);
              ?>
            </td>

            <td>
              <?php
                $idPatient = (int)($d['idPatient'] ?? 0);
                echo (int)($transfertsCount[$idPatient] ?? 0);
              ?>
            </td>
          <?php endif; ?>

          <!-- Statut + indication transfert -->
          <td>
            <?= $h($d['statut'] ?? '') ?>

            <?php
              // Récupération du dernier statut de transfert pour ce patient
              $idPatient = (int)($d['idPatient'] ?? 0);
              $lastTransfertStatut = $transfertsLastStatut[$idPatient] ?? '';
            ?>

            <?php if ($lastTransfertStatut === 'demande' || $lastTransfertStatut === 'attente_reponse'): ?>
              <br>
              <span class="text-muted">+ demande de transfert en cours</span>
            <?php endif; ?>
          </td>

          <td>
            <?php
              $role = $_SESSION['user']['role'] ?? '';
              $action = ($role === 'MEDECIN') ? 'dossier_detail_medecin' : 'dossier_detail';
            ?>
            <a class="btn" href="index.php?action=<?= $action ?>&id=<?= (int)$d['idDossier'] ?>">
              Consulter
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>