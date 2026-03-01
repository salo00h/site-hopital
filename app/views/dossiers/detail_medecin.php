<?php
declare(strict_types=1);

/*
  Vue Médecin : détail dossier + actions (examen / transfert / équipement)
  Objectif : afficher les infos du dossier + proposer des liens d'actions (MVC).
  HTML simple, CSS uniquement dans style.css.
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

// Sécurité : si la variable $dossier n'existe pas, on utilise un tableau vide
$dossier = $dossier ?? [];

// Id du dossier (converti en int pour éviter les injections via l’URL/paramètres)
$idDossier = (int)($dossier['idDossier'] ?? 0);

/**
 * Helper d'échappement HTML (anti XSS).
 * On convertit toute valeur en chaîne, puis htmlspecialchars.
 */
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/**
 * Flash message : lecture puis suppression de la session.
 * Permet d'afficher un message après une redirection.
 */
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

    <!-- Bloc d'actions (liens) : on remplace les anciens boutons / formulaires -->
    <!-- Important : si tes actions sont différentes, tu changes seulement les href -->
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
           href="index.php?action=equipement_reserver_form&idDossier=<?= $idDossier ?>">
           Réserver équipement
        </a>

    </div>

    <hr>

    <!-- Note :
         On a retiré les formulaires "Demander un examen" et "Demande de transfert"
         car maintenant ce sont des liens vers des pages/formulaires dédiés (MVC).
         Les contrôleurs correspondants pourront être branchés ensuite.
    -->
</main>

<?php require_once APP_PATH . '/includes/footer.php'; ?>