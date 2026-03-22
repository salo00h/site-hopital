<?php
declare(strict_types=1);

/*
==================================================
 VIEW : Dossier patient - Infirmier
==================================================
 Cette vue affiche :
 - les informations du patient
 - les informations du dossier
 - les actions de l'infirmier
 - les examens demandés
 - les équipements réservés
 - les demandes de transfert

 Remarque :
 - les données sont préparées par le contrôleur
 - cette vue reste simple
==================================================
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Sécurisation des données
|--------------------------------------------------------------------------
*/
$dossier = $dossier ?? [];
$examens = $examens ?? [];
$equipementsReserves = $equipementsReserves ?? [];
$transferts = $transferts ?? [];

$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$formatDateTime = static function (?string $v): string {
    if (empty($v)) {
        return '-';
    }

    $ts = strtotime($v);
    if ($ts === false) {
        return (string)$v;
    }

    return date('d/m/Y H:i', $ts);
};

/*
|--------------------------------------------------------------------------
| Dernière demande de transfert
|--------------------------------------------------------------------------
| Permet d'afficher un complément d'information
| dans le bloc "Informations dossier".
*/
$dernierTransfert = !empty($transferts) ? $transferts[0] : null;
$statutDernierTransfert = (string)($dernierTransfert['statutTransfer'] ?? '');

$libelleTransfert = '-';

if ($statutDernierTransfert !== '') {
    if ($statutDernierTransfert === 'demande') {
        $libelleTransfert = 'demande de transfert en cours';
    } elseif ($statutDernierTransfert === 'attente_reponse') {
        $libelleTransfert = 'demande de transfert en attente de réponse';
    } elseif ($statutDernierTransfert === 'accepte') {
        $libelleTransfert = 'transfert accepté';
    } elseif ($statutDernierTransfert === 'refuse') {
        $libelleTransfert = 'transfert refusé';
    } else {
        $libelleTransfert = $statutDernierTransfert;
    }
}
?>

<h1 class="page-title">Dossier #<?= (int)($dossier['idDossier'] ?? 0) ?></h1>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success">
    <?= $h($_SESSION['flash_success']) ?>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger">
    <?= $h($_SESSION['flash_error']) ?>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="dossier-grid">

  <!-- Carte : informations patient -->
  <div class="card">
    <div class="card-title">Informations patient</div>

    <table class="table">
      <tbody>
        <tr><th style="width:220px;">Nom</th><td><?= $h($dossier['nom'] ?? '') ?></td></tr>
        <tr><th>Prénom</th><td><?= $h($dossier['prenom'] ?? '') ?></td></tr>
        <tr><th>ID Patient</th><td><?= $h($dossier['idPatient'] ?? '') ?></td></tr>
        <tr><th>Date naissance</th><td><?= $h($dossier['dateNaissance'] ?? '') ?></td></tr>
        <tr><th>Genre</th><td><?= $h($dossier['genre'] ?? '') ?></td></tr>
        <tr><th>Adresse</th><td><?= $h($dossier['adresse'] ?? '-') ?></td></tr>
        <tr><th>Téléphone</th><td><?= $h($dossier['telephone'] ?? '-') ?></td></tr>
        <tr><th>Email</th><td><?= $h($dossier['email'] ?? '-') ?></td></tr>
        <tr><th>Carte Vitale</th><td><?= $h($dossier['numeroCarteVitale'] ?? '-') ?></td></tr>
        <tr><th>Mutuelle</th><td><?= $h($dossier['mutuelle'] ?? '-') ?></td></tr>
      </tbody>
    </table>
  </div>

  <!-- Carte : informations dossier -->
  <div class="card">
    <div class="card-title">Informations dossier</div>

    <table class="table">
      <tbody>
        <tr><th style="width:220px;">Date / heure arrivée</th><td><?= $formatDateTime($dossier['dateAdmission'] ?? null) ?></td></tr>
        <tr><th>Date sortie</th><td><?= $formatDateTime($dossier['dateSortie'] ?? null) ?></td></tr>

        <!-- Afficher si le médecin a déjà validé la sortie -->
        <tr>
          <th>Sortie validée par le médecin</th>
          <td><?= ((int)($dossier['sortieValidee'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
        </tr>

        <?php if (!empty($dossier['dateValidationSortie'])): ?>
        <tr>
          <th>Date validation médicale</th>
          <td><?= $h($dossier['dateValidationSortie']) ?></td>
        </tr>
        <?php endif; ?>

        <tr>
          <th>Statut</th>
          <td>
            <?= $h($dossier['statut'] ?? '') ?>

            <?php if (
                (int)($dossier['sortieValidee'] ?? 0) === 1
                && (int)($dossier['sortieConfirmee'] ?? 0) === 0
            ): ?>
              <br>
              <span class="text-muted">
                + le médecin a validé la sortie de ce patient, merci de poursuivre les opérations de sortie du patient
              </span>
            <?php endif; ?>
          </td>
        </tr>

        <tr><th>Demande de transfert</th><td><?= $h($libelleTransfert) ?></td></tr>

        <!-- Ajout : état de confirmation finale -->
        <tr>
          <th>Sortie finale confirmée</th>
          <td><?= ((int)($dossier['sortieConfirmee'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
        </tr>

        <tr><th>Niveau de priorité</th><td><?= $h($dossier['niveau'] ?? '-') ?></td></tr>
        <tr><th>Délai prise en charge</th><td><?= $h($dossier['delaiPriseCharge'] ?? '-') ?></td></tr>
        <tr><th>État entrée</th><td><?= $h($dossier['etat_entree'] ?? '-') ?></td></tr>
        <tr><th>Diagnostic</th><td><?= nl2br($h($dossier['diagnostic'] ?? '')) ?></td></tr>
        <tr><th>Traitements</th><td><?= nl2br($h($dossier['traitements'] ?? '')) ?></td></tr>
        <tr><th>Historique médical</th><td><?= nl2br($h($dossier['historiqueMedical'] ?? '')) ?></td></tr>
        <tr><th>Antécédant</th><td><?= nl2br($h($dossier['antecedant'] ?? '')) ?></td></tr>
        <tr>
          <th>Lit attribué</th>
          <td>
            <?php if (!empty($dossier['numeroLit'])): ?>
              n° <?= $h($dossier['numeroLit']) ?>

              <?php
              // Afficher l'action seulement pour l'infirmier
              // quand le lit existe et qu'il est encore réservé.
              $role = $_SESSION['user']['role'] ?? '';
              $canConfirmInstallation =
                  $role === 'INFIRMIER' &&
                  !empty($dossier['idLit']) &&
                  (($dossier['etatLit'] ?? '') === 'reserve');
              ?>

              <?php if ($canConfirmInstallation): ?>
                <form
                  method="post"
                  action="index.php?action=confirmer_installation_patient"
                  class="install-inline-form"
                  data-install-form
                  data-lit-numero="<?= $h($dossier['numeroLit']) ?>"
                >
                  <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">

                  <button type="submit" class="btn-install">
                    ✔ Installer
                  </button>

                  <span class="install-inline-note">(lit réservé)</span>
                </form>
              <?php endif; ?>

            <?php else: ?>
              -
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Actions selon le rôle -->
    <div class="actions">
      <a class="btn" href="index.php?action=dossiers_list">← Retour liste</a>

      <?php if (($_SESSION['user']['role'] ?? '') === 'INFIRMIER_ACCUEIL'): ?>
        <a class="btn" href="index.php?action=dossier_edit_form&id=<?= (int)$dossier['idDossier'] ?>">
          Modifier
        </a>

        <?php if (empty($dossier['idLit'])): ?>
          <a class="btn btn-primary" href="index.php?action=lit_reserver_form&idDossier=<?= (int)$dossier['idDossier'] ?>">
            Réserver un lit
          </a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (($_SESSION['user']['role'] ?? '') === 'INFIRMIER'): ?>
        <a class="btn btn-primary" href="index.php?action=equipements_list_infirmier&idDossier=<?= (int)$dossier['idDossier'] ?>">
          Réserver équipement
        </a>
      <?php endif; ?>

      <?php if (
          (int)($dossier['sortieValidee'] ?? 0) === 1
          && (int)($dossier['sortieConfirmee'] ?? 0) === 0
      ): ?>
        <form method="post" action="index.php?action=confirmerSortieInfirmier" style="display:inline-block;">
          <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">
          <button
            type="submit"
            class="btn btn-success"
            onclick="return confirm('Confirmer la sortie finale et libérer le lit ?');"
          >
            Confirmer sortie finale
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div>

</div>

<?php
$role = $_SESSION['user']['role'] ?? '';
$statutDossier = (string)($dossier['statut'] ?? '');
?>

<?php if (
    $role === 'INFIRMIER' &&
    ($statutDossier === 'attente_examen' || $statutDossier === 'attente_resultat')
): ?>
  <div class="card">
    <div class="card-title">Examens demandés</div>

    <?php if (empty($examens)): ?>
      <p>Aucun examen demandé pour ce dossier.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th style="width:180px;">Type</th>
            <th>Note médecin</th>
            <th style="width:170px;">Date demande</th>
            <th style="width:140px;">Statut</th>
            <th>Résultat</th>
            <th style="width:190px;">Date résultat</th>
            <th style="width:220px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($examens as $examen): ?>
            <tr>
              <td><?= $h($examen['typeExamen'] ?? '') ?></td>
              <td><?= nl2br($h($examen['noteMedecin'] ?? '')) ?></td>
              <td><?= $formatDateTime($examen['dateDemande'] ?? null) ?></td>
              <td><?= $h($examen['statut'] ?? '') ?></td>

              <td>
                <?php if (!empty($examen['resultat'])): ?>
                  <?= nl2br($h($examen['resultat'])) ?>
                <?php else: ?>
                  <span class="text-muted">Non saisi</span>
                <?php endif; ?>
              </td>

              <td><?= $formatDateTime($examen['dateResultat'] ?? null) ?></td>

              <td>
                <?php if (($examen['statut'] ?? '') === 'EN_ATTENTE'): ?>
                  <form method="post" action="index.php?action=examen_realiser" style="margin-bottom:8px;">
                    <input type="hidden" name="idExamen" value="<?= (int)$examen['idExamen'] ?>">
                    <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">
                    <button type="submit" class="btn">Réaliser examen</button>
                  </form>

                <?php elseif (
                    ($examen['statut'] ?? '') === 'EN_COURS'
                    && empty($examen['resultat'])
                ): ?>
                  <form method="post" action="index.php?action=examen_saisir_resultat">
                    <input type="hidden" name="idExamen" value="<?= (int)$examen['idExamen'] ?>">
                    <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">

                    <textarea
                      name="resultat"
                      rows="3"
                      style="width:100%; margin-bottom:8px;"
                      placeholder="Saisir le résultat de l’examen..."
                      required
                    ></textarea>

                    <button type="submit" class="btn">Enregistrer résultat</button>
                  </form>

                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Carte : équipements réservés -->
<div class="card">
  <div class="card-title">Équipements réservés</div>

  <?php if (empty($equipementsReserves)): ?>
    <p>Aucun équipement réservé.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Numéro</th>
          <th>Localisation</th>
          <th>État</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($equipementsReserves as $eq): ?>
          <tr>
            <td><?= $h($eq['typeEquipement'] ?? '-') ?></td>
            <td><?= $h($eq['numeroEquipement'] ?? '-') ?></td>
            <td><?= $h($eq['localisation'] ?? '-') ?></td>

            <td>
              <?php
              /*
              |--------------------------------------------------------------------------
              | Affichage de l'état + actions rapides pour l'infirmier
              |--------------------------------------------------------------------------
              | - reserve => afficher bouton Utiliser
              | - occupe  => afficher bouton Libérer
              | Ces actions sont visibles seulement pour le rôle INFIRMIER.
              */
              $etatEq = (string)($eq['etatEquipement'] ?? '');
              ?>

              <?= $h($etatEq !== '' ? $etatEq : '-') ?>

              <?php if (($_SESSION['user']['role'] ?? '') === 'INFIRMIER'): ?>
                <?php if ($etatEq === 'reserve'): ?>
                  <form method="post"
                        action="index.php?action=equipement_utiliser"
                        style="display:inline-block; margin-left:10px;">
                    <input type="hidden" name="idEquipement" value="<?= (int)($eq['idEquipement'] ?? 0) ?>">
                    <input type="hidden" name="idDossier" value="<?= (int)($dossier['idDossier'] ?? 0) ?>">
                    <button type="submit" class="btn btn-primary">
                      Utiliser
                    </button>
                  </form>

                <?php elseif ($etatEq === 'occupe'): ?>
                  <form method="post"
                        action="index.php?action=equipement_liberer"
                        style="display:inline-block; margin-left:10px;"
                        onsubmit="return confirm('Libérer cet équipement ?');">
                    <input type="hidden" name="idEquipement" value="<?= (int)($eq['idEquipement'] ?? 0) ?>">
                    <input type="hidden" name="idDossier" value="<?= (int)($dossier['idDossier'] ?? 0) ?>">
                    <button type="submit" class="btn">
                      Libérer
                    </button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Carte : demandes de transfert -->
<div class="card">
  <div class="card-title">Demandes de transfert</div>

  <?php if (empty($transferts)): ?>
    <p>Aucune demande de transfert.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Hôpital destinataire</th>
          <th>Service destinataire</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transferts as $t): ?>
          <tr>
            <td><?= $formatDateTime($t['dateCreation'] ?? null) ?></td>
            <td><?= $h($t['hopitalDestinataire'] ?? '-') ?></td>
            <td><?= $h($t['serviceDestinataire'] ?? '-') ?></td>
            <td><?= $h($t['statutTransfer'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div id="installModal" class="custom-modal hidden" aria-hidden="true">
  <div class="custom-modal-box">
    <h3 class="custom-modal-title">Confirmation</h3>
    <p id="installModalText" class="custom-modal-text"></p>

    <div class="custom-modal-actions">
      <button type="button" class="btn btn-light" id="installModalCancel">
        Annuler
      </button>
      <button type="button" class="btn btn-primary" id="installModalConfirm">
        Confirmer
      </button>
    </div>
  </div>
</div>

<script src="assets/js/install-confirmation.js"></script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>