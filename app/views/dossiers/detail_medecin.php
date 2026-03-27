<?php
declare(strict_types=1);

/*
==================================================
 VIEW : Dossier patient - Médecin
==================================================
 Cette vue affiche uniquement les informations
 nécessaires à l'interface du médecin :
 - informations du patient
 - informations du dossier
 - actions disponibles
 - examens demandés
 - équipements réservés
 - demandes de transfert

 Ce fichier doit rester une vue claire et lisible.
 Pas de logique métier ici.
 Pas de CSS ici.
 Uniquement l'affichage et la structure.
==================================================
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Initialisation des données pour l'affichage
|--------------------------------------------------------------------------
| La vue récupère uniquement les données déjà préparées
| afin de garder un code robuste et simple à lire.
|--------------------------------------------------------------------------
*/
$dossier = $dossier ?? [];
$idDossier = (int) ($dossier['idDossier'] ?? 0);

$equipementsReserves = $equipementsReserves ?? [];
$examens = $examens ?? [];
$transferts = $transferts ?? [];

/*
|--------------------------------------------------------------------------
| Sécurisation des sorties HTML
|--------------------------------------------------------------------------
| Toute donnée affichée dans la vue passe par un échappement
| pour garder un affichage propre et sécurisé.
|--------------------------------------------------------------------------
*/
$h = static function (mixed $v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/*
|--------------------------------------------------------------------------
| Formatage date / heure pour l'affichage
|--------------------------------------------------------------------------
| Cette fonction aide seulement à présenter les dates
| de manière lisible dans la vue.
|--------------------------------------------------------------------------
*/
$formatDateTime = static function (?string $v): string {
    if (empty($v)) {
        return '-';
    }

    $ts = strtotime($v);
    if ($ts === false) {
        return (string) $v;
    }

    return date('d/m/Y H:i', $ts);
};

/*
|--------------------------------------------------------------------------
| Messages flash
|--------------------------------------------------------------------------
| Les messages sont affichés ici car ils concernent le rendu utilisateur.
|--------------------------------------------------------------------------
*/
$flashSuccess = '';
$flashError = '';

if (!empty($_SESSION['flash_success'])) {
    $flashSuccess = (string) $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (!empty($_SESSION['flash_error'])) {
    $flashError = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

/*
|--------------------------------------------------------------------------
| Dernier transfert
|--------------------------------------------------------------------------
| Préparation d'un libellé d'affichage simple pour la vue.
| Cela permet de garder le tableau du dossier plus lisible.
|--------------------------------------------------------------------------
*/
$dernierTransfert = !empty($transferts) ? $transferts[0] : null;
$statutDernierTransfert = (string) ($dernierTransfert['statutTransfer'] ?? '');

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
    <div class="alert alert-success">
        <?= $h($flashSuccess) ?>
    </div>
<?php endif; ?>

<?php if ($flashError !== ''): ?>
    <div class="alert alert-danger">
        <?= $h($flashError) ?>
    </div>
<?php endif; ?>

<div class="dossier-grid">

    <!-- Bloc : informations patient -->
    <div class="card">
        <h2 class="card-title">Informations patient</h2>

        <table class="table">
            <tbody>
                <tr>
                    <th>Nom</th>
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
                    <th>Date naissance</th>
                    <td><?= $h($dossier['dateNaissance'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Genre</th>
                    <td><?= $h($dossier['genre'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Adresse</th>
                    <td><?= $h($dossier['adresse'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Téléphone</th>
                    <td><?= $h($dossier['telephone'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?= $h($dossier['email'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Carte Vitale</th>
                    <td><?= $h($dossier['numeroCarteVitale'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Mutuelle</th>
                    <td><?= $h($dossier['mutuelle'] ?? '-') ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bloc : informations dossier -->
    <div class="card">
        <h2 class="card-title">Informations dossier</h2>

        <table class="table">
            <tbody>
                <tr>
                    <th>Date / heure arrivée</th>
                    <td><?= $formatDateTime($dossier['dateAdmission'] ?? null) ?></td>
                </tr>
                <tr>
                    <th>Date sortie</th>
                    <td><?= $formatDateTime($dossier['dateSortie'] ?? null) ?></td>
                </tr>

                <tr>
                    <th>Sortie validée</th>
                    <td><?= ((int) ($dossier['sortieValidee'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
                </tr>

                <?php if (!empty($dossier['dateValidationSortie'])): ?>
                    <tr>
                        <th>Date validation sortie</th>
                        <td><?= $h($dossier['dateValidationSortie']) ?></td>
                    </tr>
                <?php endif; ?>

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

                        <?php if (
                            (int) ($dossier['sortieValidee'] ?? 0) === 1
                            && (int) ($dossier['sortieConfirmee'] ?? 0) === 0
                        ): ?>
                            <br>
                            <span class="text-muted">
                                + sortie médicale validée, en attente de l’infirmier pour finaliser les opérations de sortie du patient
                            </span>
                        <?php endif; ?>

                        <?php
                        /*
                        ----------------------------------------------------------------------
                        Affichage conditionnel d'une action dans la vue
                        ----------------------------------------------------------------------
                        La vue affiche simplement le bouton si les conditions déjà
                        fournies par les données le permettent.
                        La logique métier reste hors de ce fichier.
                        ----------------------------------------------------------------------
                        */
                        $role = $_SESSION['user']['role'] ?? '';
                        $statut = $dossier['statut'] ?? '';

                        $canStartConsultation =
                            $role === 'MEDECIN'
                            && ($statut === 'attente_consultation' || $statut === 'attente_resultat');
                        ?>

                        <?php if ($canStartConsultation): ?>
                            <form method="post" action="index.php?action=dossier_commencer_consultation" class="install-inline-form">
                                <input type="hidden" name="idDossier" value="<?= (int) $dossier['idDossier'] ?>">

                                <?php if ($statut === 'attente_resultat'): ?>
                                    <button type="submit" class="btn-install">Analyser résultats</button>
                                <?php else: ?>
                                    <button type="submit" class="btn-install">Commencer consultation</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th>Demande de transfert</th>
                    <td><?= $h($libelleTransfert) ?></td>
                </tr>
                <tr>
                    <th>Niveau de priorité</th>
                    <td><?= $h($dossier['niveau'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Délai prise en charge</th>
                    <td><?= $h($dossier['delaiPriseCharge'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>État entrée</th>
                    <td><?= $h($dossier['etat_entree'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Motif</th>
                    <td><?= $h($dossier['motifAdmission'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Diagnostic</th>
                    <td><?= nl2br($h($dossier['diagnostic'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th>Traitements</th>
                    <td><?= nl2br($h($dossier['traitements'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th>Historique médical</th>
                    <td><?= nl2br($h($dossier['historiqueMedical'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th>Antécédant</th>
                    <td><?= nl2br($h($dossier['antecedant'] ?? '')) ?></td>
                </tr>
                <tr>
                    <th>Lit attribué</th>
                    <td><?= $h($dossier['numeroLit'] ?? 'Non attribué') ?></td>
                </tr>
            </tbody>
        </table>

        <?php $statutDossier = (string) ($dossier['statut'] ?? ''); ?>

        <div class="actions">
            <a class="btn" href="index.php?action=dossiers_list">Retour liste</a>

            <a class="btn" href="index.php?action=dossier_edit_medecin_form&id=<?= $idDossier ?>">
                Modifier dossier
            </a>

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

            <?php if (($dossier['statut'] ?? '') !== 'ferme' && (int) ($dossier['sortieValidee'] ?? 0) === 0): ?>
                <form method="post" action="index.php?action=validerSortieMedecin" style="display:inline-block;">
                    <input type="hidden" name="idDossier" value="<?= (int) $dossier['idDossier'] ?>">
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

<!-- Bloc : examens demandés -->
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

<!-- Bloc : équipements réservés -->
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
                        <td>
                            <?php $etatEq = (string) ($eq['etatEquipement'] ?? ''); ?>
                            <?= $h($etatEq !== '' ? $etatEq : '-') ?>

                            <?php if (($_SESSION['user']['role'] ?? '') === 'MEDECIN'): ?>
                                <?php if ($etatEq === 'reserve'): ?>
                                    <form
                                        method="post"
                                        action="index.php?action=equipement_utiliser_medecin"
                                        style="display:inline-block; margin-left:10px;"
                                    >
                                        <input type="hidden" name="idEquipement" value="<?= (int) ($eq['idEquipement'] ?? 0) ?>">
                                        <input type="hidden" name="idDossier" value="<?= (int) ($dossier['idDossier'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-primary">Utiliser</button>
                                    </form>
                                <?php elseif ($etatEq === 'occupe'): ?>
                                    <form
                                        method="post"
                                        action="index.php?action=equipement_liberer_medecin"
                                        style="display:inline-block; margin-left:10px;"
                                        onsubmit="return confirm('Libérer cet équipement ?');"
                                    >
                                        <input type="hidden" name="idEquipement" value="<?= (int) ($eq['idEquipement'] ?? 0) ?>">
                                        <input type="hidden" name="idDossier" value="<?= (int) ($dossier['idDossier'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-danger">Libérer</button>
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

<!-- Bloc : demandes de transfert -->
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