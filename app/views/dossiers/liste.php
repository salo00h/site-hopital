<?php
/*
==================================================
 VIEW : Liste des dossiers
==================================================
 Ce fichier correspond uniquement à l'affichage
 de la liste des dossiers dans l'interface.

 Cette vue doit rester :
 - simple
 - lisible
 - bien structurée
 - sans logique métier
 - sans CSS
 - centrée uniquement sur l'affichage
==================================================
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/** @var array $dossiers */

$role = $_SESSION['user']['role'] ?? '';
$isMedecin = in_array($role, ['MEDECIN', 'INFIRMIER'], true);
$isAccueil = ($role === 'INFIRMIER_ACCUEIL');

/*
|--------------------------------------------------------------------------
| Helper d'affichage
|--------------------------------------------------------------------------
| Cette fonction sert uniquement à sécuriser les données
| affichées dans la vue.
|--------------------------------------------------------------------------
*/
$h = static function (mixed $v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1 class="page-title">Liste des dossiers</h1>

<form class="actions" method="get" action="index.php">
    <input type="hidden" name="action" value="dossiers_list">

    <input
        type="text"
        name="q"
        placeholder="Rechercher (nom/prénom/id)"
        value="<?= $h($q ?? '') ?>"
    >

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
                    <td><?= (int) ($d['idDossier'] ?? 0) ?></td>
                    <td><?= $h(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?></td>
                    <td><?= $h($d['dateNaissance'] ?? '') ?></td>
                    <td><?= $h($d['genre'] ?? '') ?></td>
                    <td><?= $h($d['dateAdmission'] ?? '') ?></td>
                    <td><?= $h($d['niveau'] ?? '-') ?></td>
                    <td><?= !empty($d['numeroLit']) ? $h($d['numeroLit']) : '-' ?></td>

                    <?php if ($isMedecin): ?>
                        <td>
                            <?php
                            $idDossier = (int) ($d['idDossier'] ?? 0);
                            echo !empty($equipementsResume[$idDossier]) ? 'Oui' : '-';
                            ?>
                        </td>

                        <td>
                            <?php
                            $idDossier = (int) ($d['idDossier'] ?? 0);
                            echo (int) ($examensCount[$idDossier] ?? 0);
                            ?>
                        </td>

                        <td>
                            <?php
                            $idPatient = (int) ($d['idPatient'] ?? 0);
                            echo (int) ($transfertsCount[$idPatient] ?? 0);
                            ?>
                        </td>
                    <?php endif; ?>

                    <td>
                        <?php
                        /*
                        ----------------------------------------------------------------------
                        Affichage du statut dans la vue
                        ----------------------------------------------------------------------
                        Cette partie reste côté affichage :
                        - adaptation visuelle du texte du statut
                        - ajout d'informations complémentaires visibles à l'écran
                        - aucune logique métier métier ne doit être déplacée ici
                        ----------------------------------------------------------------------
                        */
                        $statut = (string) ($d['statut'] ?? '');
                        $sortieValidee = (int) ($d['sortieValidee'] ?? 0);
                        $sortieConfirmee = (int) ($d['sortieConfirmee'] ?? 0);

                        if ($statut === 'consultation') {
                            echo 'EN CONSULTATION';
                        } else {
                            echo $h($statut);
                        }

                        $idPatient = (int) ($d['idPatient'] ?? 0);
                        $lastTransfertStatut = $transfertsLastStatut[$idPatient] ?? '';
                        ?>

                        <?php if ($sortieValidee === 1 && $sortieConfirmee === 0): ?>
                            <br>
                            <span class="text-muted">
                                + sortie validée par le médecin, en attente de l’infirmier
                            </span>
                        <?php endif; ?>

                        <?php if ($lastTransfertStatut === 'demande' || $lastTransfertStatut === 'attente_reponse'): ?>
                            <br>
                            <span class="text-muted">+ demande de transfert en cours</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php
                        /*
                        ----------------------------------------------------------------------
                        Choix du lien de détail pour l'affichage
                        ----------------------------------------------------------------------
                        La vue oriente simplement vers la bonne page selon le rôle.
                        Cela reste une organisation d'interface.
                        ----------------------------------------------------------------------
                        */
                        $currentRole = $_SESSION['user']['role'] ?? '';
                        $detailAction = ($currentRole === 'MEDECIN') ? 'dossier_detail_medecin' : 'dossier_detail';
                        ?>

                        <a class="btn" href="index.php?action=<?= $detailAction ?>&id=<?= (int) ($d['idDossier'] ?? 0) ?>">
                            Consulter
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>