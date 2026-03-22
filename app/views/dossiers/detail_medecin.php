<?php
declare(strict_types=1);

/*
==================================================
 VIEW : Dossier patient - Médecin
==================================================
 Cette vue affiche :
 - les informations du patient
 - les informations du dossier
 - les actions du médecin selon le statut
 - les examens déjà demandés
 - les équipements réservés
 - les demandes de transfert

 Remarque :
 - les données sont préparées par le contrôleur
 - cette vue reste simple
 - l'affichage du statut utilise un badge CSS coloré
==================================================
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Sécurisation des données
|--------------------------------------------------------------------------
| On initialise les variables pour éviter les warnings
| si une donnée manque dans le contrôleur.
*/
$dossier = $dossier ?? [];
$idDossier = (int)($dossier['idDossier'] ?? 0);

$equipementsReserves = $equipementsReserves ?? [];
$examens = $examens ?? [];
$transferts = $transferts ?? [];

/*
|--------------------------------------------------------------------------
| Fonction simple pour sécuriser l'affichage HTML
|--------------------------------------------------------------------------
*/
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/*
|--------------------------------------------------------------------------
| Format simple des dates
|--------------------------------------------------------------------------
| Si la date est vide on affiche "-"
| Sinon on affiche au format jour/mois/année heure:minute
*/
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
| Messages flash
|--------------------------------------------------------------------------
| On récupère les messages de succès / erreur
| puis on les supprime de la session après affichage.
*/
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

/*
|--------------------------------------------------------------------------
| Dernière demande de transfert
|--------------------------------------------------------------------------
| On récupère la dernière demande pour afficher
| un état complémentaire dans les informations du dossier.
*/
$dernierTransfert = !empty($transferts) ? $transferts[0] : null;
$statutDernierTransfert = (string)($dernierTransfert['statutTransfer'] ?? '');

/*
|--------------------------------------------------------------------------
| Libellé simple pour l'état du transfert
|--------------------------------------------------------------------------
| Ce texte complète le statut médical du dossier
| sans modifier la logique de la base de données.
*/
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

<h1 class="page-title">Dossier patient - Médecin</h1>

<?php if ($flashSuccess !== ''): ?>
  <div class="alert alert-success"><?= $h($flashSuccess) ?></div>
<?php endif; ?>

<?php if ($flashError !== ''): ?>
  <div class="alert alert-danger"><?= $h($flashError) ?></div>
<?php endif; ?>

<div class="dossier-grid">

  <!-- Carte : informations patient -->
  <div class="card">
    <h2 class="card-title">Informations patient</h2>

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
    <h2 class="card-title">Informations dossier</h2>

    <table class="table">
      <tbody>
        <tr><th style="width:220px;">Date / heure arrivée</th><td><?= $formatDateTime($dossier['dateAdmission'] ?? null) ?></td></tr>
        <tr><th>Date sortie</th><td><?= $formatDateTime($dossier['dateSortie'] ?? null) ?></td></tr>

        <!-- Ajout : état de validation de sortie -->
        <tr>
          <th>Sortie validée</th>
          <td><?= ((int)($dossier['sortieValidee'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
        </tr>

        <?php if (!empty($dossier['dateValidationSortie'])): ?>
        <tr>
          <th>Date validation sortie</th>
          <td><?= htmlspecialchars($dossier['dateValidationSortie'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endif; ?>

        <!-- Affichage du statut avec un badge coloré -->
        <tr>
          <th>Statut</th>
          <td>
            <span class="badge statut-<?= $h($dossier['statut'] ?? '') ?>">
              <?= $h($dossier['statut'] ?? '') ?>
            </span>

            <?php if ($statutDernierTransfert === 'demande' || $statutDernierTransfert === 'attente_reponse'): ?>
              <br>
              <span class="text-muted">+ <?= $h($libelleTransfert) ?></span>
            <?php endif; ?>

            <?php
            // Afficher une information métier après validation médicale
            // tant que la sortie finale n'est pas encore confirmée.
            ?>
            <?php if (
                (int)($dossier['sortieValidee'] ?? 0) === 1
                && (int)($dossier['sortieConfirmee'] ?? 0) === 0
            ): ?>
              <br>
              <span class="text-muted">
                + sortie médicale validée, en attente de l’infirmier pour finaliser les opérations de sortie du patient
              </span>
            <?php endif; ?>

            <?php
            /*
            --------------------------------------------------
            Le bouton dépend du rôle et du statut du dossier
            --------------------------------------------------
            - le médecin peut commencer la consultation
            - ou analyser les résultats selon l'état actuel
            */
            $role = $_SESSION['user']['role'] ?? '';
            $statut = $dossier['statut'] ?? '';

            $canStartConsultation =
                $role === 'MEDECIN' &&
                ($statut === 'attente_consultation' || $statut === 'attente_resultat');
            ?>

            <?php if ($canStartConsultation): ?>
              <form
                method="post"
                action="index.php?action=dossier_commencer_consultation"
                class="install-inline-form"
              >
                <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">

                <?php if ($statut === 'attente_resultat'): ?>
                  <button type="submit" class="btn-install">
                    ▶ Analyser résultats
                  </button>
                <?php else: ?>
                  <button type="submit" class="btn-install">
                    ▶ Commencer Consultation
                  </button>
                <?php endif; ?>
              </form>
            <?php endif; ?>
          </td>
        </tr>

        <!-- Affichage séparé de l'état de transfert -->
        <tr>
          <th>Demande de transfert</th>
          <td><?= $h($libelleTransfert) ?></td>
        </tr>

        <tr><th>Niveau de priorité</th><td><?= $h($dossier['niveau'] ?? '-') ?></td></tr>
        <tr><th>Délai prise en charge</th><td><?= $h($dossier['delaiPriseCharge'] ?? '-') ?></td></tr>
        <tr><th>État entrée</th><td><?= $h($dossier['etat_entree'] ?? '-') ?></td></tr>
        <tr><th>Motif</th><td><?= $h($dossier['motifAdmission'] ?? '-') ?></td></tr>
        <tr><th>Diagnostic</th><td><?= nl2br($h($dossier['diagnostic'] ?? '')) ?></td></tr>
        <tr><th>Traitements</th><td><?= nl2br($h($dossier['traitements'] ?? '')) ?></td></tr>
        <tr><th>Historique médical</th><td><?= nl2br($h($dossier['historiqueMedical'] ?? '')) ?></td></tr>
        <tr><th>Antécédant</th><td><?= nl2br($h($dossier['antecedant'] ?? '')) ?></td></tr>
        <tr><th>Lit attribué</th><td><?= $h($dossier['numeroLit'] ?? 'Non attribué') ?></td></tr>
      </tbody>
    </table>

    <?php $statutDossier = (string)($dossier['statut'] ?? ''); ?>

    <!-- Actions disponibles pour le médecin -->
    <div class="actions">
      <a class="btn" href="index.php?action=dossiers_list">← Retour liste</a>

      <?php if ($statutDossier === 'consultation' || $statutDossier === 'attente_examen'): ?>
        <a class="btn" href="index.php?action=examen_form&idDossier=<?= $idDossier ?>">
          Demander examen
        </a>

        <a class="btn" href="index.php?action=transfert_form&idDossier=<?= $idDossier ?>">
          Demander transfert
        </a>

        <a class="btn btn-primary" href="index.php?action=equipements_list_medecin&idDossier=<?= $idDossier ?>">
          Réserver équipement
        </a>
      <?php endif; ?>

      <?php if (($dossier['statut'] ?? '') !== 'ferme' && (int)($dossier['sortieValidee'] ?? 0) === 0): ?>
        <form method="post" action="index.php?action=validerSortieMedecin" style="display:inline-block;">
          <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">
          <button
            type="submit"
            class="btn btn-danger"
            onclick="return confirm('Confirmer la sortie du patient ?');"
          >
            Valider sortie
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Carte : examens déjà demandés -->
<div class="card">
  <h2 class="card-title">Examens demandés</h2>

  <?php if (empty($examens)): ?>
    <p>Aucun examen demandé.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Type</th>
          <th>Note médecin</th>
          <th>Date demande</th>
          <th>Statut</th>
          <th>Résultat</th>
          <th>Date résultat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($examens as $ex): ?>
          <tr>
            <td><?= $h($ex['typeExamen'] ?? '') ?></td>
            <td><?= nl2br($h($ex['noteMedecin'] ?? '')) ?></td>
            <td><?= $formatDateTime($ex['dateDemande'] ?? null) ?></td>
            <td><?= $h($ex['statut'] ?? '') ?></td>

            <td>
              <?php if (!empty($ex['resultat'])): ?>
                <?= nl2br($h($ex['resultat'])) ?>
              <?php else: ?>
                <span class="text-muted">En attente</span>
              <?php endif; ?>
            </td>

            <td><?= $formatDateTime($ex['dateResultat'] ?? null) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Carte : équipements réservés pour ce dossier -->
<div class="card">
  <h2 class="card-title">Équipements réservés</h2>

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
            <td><?= $h($eq['etatEquipement'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Carte : demandes de transfert liées au patient -->
<div class="card">
  <h2 class="card-title">Demandes de transfert</h2>

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

<?php require_once APP_PATH . '/includes/footer.php'; ?>