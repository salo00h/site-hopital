<?php
declare(strict_types=1);

/*
  Vue Médecin : détail dossier + actions (examen / transfert / équipement)
  Objectif : afficher les infos du dossier + proposer des liens d'actions (MVC).
  + Afficher la liste des équipements réservés pour ce dossier (UNIQUEMENT côté médecin).
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/* Sécurité : si la variable $dossier n'existe pas, on utilise un tableau vide */
$dossier = $dossier ?? [];

/* Id du dossier (converti en int pour éviter les injections via l’URL/paramètres) */
$idDossier = (int)($dossier['idDossier'] ?? 0);

/*
  Équipements réservés :
  Le contrôleur dossier_detail_medecin() doit préparer $equipementsReserves
  via gestion_equipements_by_dossier($idDossier).
  Ici, on sécurise au cas où la variable n'est pas définie.
*/
$equipementsReserves = $equipementsReserves ?? [];

/* Helper d'échappement HTML (anti XSS). */
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/* Flash message : lecture puis suppression de la session. */
function flash(string $key): string
{
    if (empty($_SESSION[$key])) {
        return '';
    }
    $msg = (string)$_SESSION[$key];
    unset($_SESSION[$key]);
    return $msg;
}

$flashSuccess = flash('flash_success');
$flashError   = flash('flash_error');
?>

<main>
    <h2>Dossier patient - Médecin</h2>

    <?php if ($flashSuccess !== ''): ?>
        <p><?= $h($flashSuccess) ?></p>
    <?php endif; ?>

    <?php if ($flashError !== ''): ?>
        <p><?= $h($flashError) ?></p>
    <?php endif; ?>

    <h3>Informations</h3>
    <p><b>Nom :</b> <?= $h($dossier['nom'] ?? '') ?></p>
    <p><b>Prénom :</b> <?= $h($dossier['prenom'] ?? '') ?></p>
    <p><b>ID Patient :</b> <?= $h($dossier['idPatient'] ?? '') ?></p>
    <p><b>Motif :</b> <?= $h($dossier['motifAdmission'] ?? '') ?></p>
    <p><b>Date admission :</b> <?= $h($dossier['dateAdmission'] ?? '') ?></p>
    <p><b>Lit attribué :</b> <?= $h($dossier['numeroLit'] ?? 'Non attribué') ?></p>

    <hr>

    <!-- Bloc d'actions (liens) -->
    <div class="dossier-actions">
        <a class="btn-action" href="index.php?action=dossiers_list">← Retour liste</a>

        <a class="btn-action btn-primary"
           href="index.php?action=dossier_edit_form&id=<?= $idDossier ?>">
           Modifier dossier
        </a>

        <a class="btn-action"
           href="index.php?action=dossier_demander_examen&id=<?= $idDossier ?>">
           Demander examen
        </a>

        <a class="btn-action"
           href="index.php?action=dossier_demander_transfert&id=<?= $idDossier ?>">
           Demander transfert
        </a>

        <a class="btn-action"
           href="index.php?action=equipements_list_medecin&idDossier=<?= $idDossier ?>">
           Réserver équipement
        </a>
    </div>

    <hr>

    <!-- =========================================================
         Section médecin : équipements réservés pour ce dossier
         Pourquoi ?
         - Visualiser rapidement quels équipements sont déjà associés au dossier
         - Améliorer le suivi (traçabilité) côté médecin
    ========================================================== -->
    <div class="card">
        <h3>Équipements réservés</h3>

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

    <!-- Note :
         Ne pas dupliquer cette section côté infirmier (demande du cahier des charges). -->
</main>

<?php require_once APP_PATH . '/includes/footer.php'; ?>