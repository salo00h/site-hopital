<?php
/*
|--------------------------------------------------------------------------
| Vue : fiche équipement
|--------------------------------------------------------------------------
| Ce fichier est une vue MVC :
| - uniquement affichage et structure HTML/PHP
| - pas de logique métier ici
| - pas de CSS ici
| - ce fichier doit rester simple et lisible
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<section class="tech-board">
    <h1 class="page-title">Fiche équipement</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="tech-detail-card">
        <?php
        /*
        |--------------------------------------------------------------------------
        | Informations principales de l'équipement
        |--------------------------------------------------------------------------
        | Cette partie affiche uniquement les données préparées avant la vue.
        | La vue reste lisible et se limite à la présentation.
        |--------------------------------------------------------------------------
        */
        ?>
        <p>
            <strong>Type :</strong>
            <?= htmlspecialchars((string) $equipement['typeEquipement']) ?>
        </p>

        <p>
            <strong>N° :</strong>
            <?= (int) $equipement['numeroEquipement'] ?>
        </p>

        <p>
            <strong>Service :</strong>
            <?= htmlspecialchars((string) ($equipement['serviceNom'] ?? '—')) ?>
        </p>

        <p>
            <strong>Localisation :</strong>
            <?= htmlspecialchars((string) ($equipement['localisation'] ?? '—')) ?>
        </p>

        <p>
            <strong>État :</strong>

            <?php
            /*
            |--------------------------------------------------------------------------
            | Préparation de l'affichage de l'état
            |--------------------------------------------------------------------------
            | Ici, on organise seulement la correspondance visuelle de l'état
            | pour garder un affichage clair dans la vue.
            |--------------------------------------------------------------------------
            */
            $etat = (string) ($equipement['etatEquipement'] ?? '');

            $classe = match ($etat) {
                'disponible'  => 'badge-success',
                'en_panne'    => 'badge-danger',
                'maintenance' => 'badge-warning',
                'HS'          => 'badge-dark',
                default       => 'badge-secondary',
            };
            ?>

            <span class="badge <?= htmlspecialchars($classe) ?>">
                <?= htmlspecialchars($etat) ?>
            </span>
        </p>

        <hr>

        <?php
        /*
        |--------------------------------------------------------------------------
        | Dernière maintenance
        |--------------------------------------------------------------------------
        | La vue affiche simplement les informations disponibles sans gérer
        | le traitement métier.
        |--------------------------------------------------------------------------
        */
        ?>
        <h3>Dernière maintenance</h3>

        <?php if ($maintenance): ?>
            <p>
                <strong>Date début :</strong>
                <?= htmlspecialchars((string) $maintenance['dateDebutEquipement']) ?>
            </p>

            <p>
                <strong>Date fin :</strong>
                <?= htmlspecialchars((string) ($maintenance['dateFinEquipement'] ?? 'En cours')) ?>
            </p>

            <p>
                <strong>Problème :</strong>
                <?= htmlspecialchars((string) $maintenance['problemeEquipement']) ?>
            </p>
        <?php else: ?>
            <p>Aucune maintenance enregistrée.</p>
        <?php endif; ?>

        <hr>

        <?php
        /*
        |--------------------------------------------------------------------------
        | Actions d'affichage selon l'état courant
        |--------------------------------------------------------------------------
        | Cette vue ne décide pas de la logique métier.
        | Elle affiche seulement les formulaires utiles selon les données reçues.
        |--------------------------------------------------------------------------
        */
        ?>
        <?php if (($equipement['etatEquipement'] ?? '') === 'en_panne'): ?>
            <form method="post" action="index.php?action=equipement_changer_etat">
                <input
                    type="hidden"
                    name="idEquipement"
                    value="<?= (int) $equipement['idEquipement'] ?>"
                >
                <input type="hidden" name="etat" value="maintenance">

                <label>Diagnostic / problème</label>
                <textarea name="probleme" rows="4" required>Diagnostic de l’équipement <?= htmlspecialchars((string) $equipement['typeEquipement']) ?> n°<?= (int) $equipement['numeroEquipement'] ?></textarea>

                <button type="submit" class="btn btn-warning">
                    Passer en maintenance
                </button>
            </form>
        <?php endif; ?>

        <?php if (($equipement['etatEquipement'] ?? '') === 'maintenance'): ?>
            <form
                method="post"
                action="index.php?action=equipement_changer_etat"
                style="margin-bottom:10px;"
            >
                <input
                    type="hidden"
                    name="idEquipement"
                    value="<?= (int) $equipement['idEquipement'] ?>"
                >
                <input type="hidden" name="etat" value="disponible">
                <input
                    type="hidden"
                    name="probleme"
                    value="Maintenance terminée - équipement réparé"
                >

                <button type="submit" class="btn btn-success">
                    Mettre disponible
                </button>
            </form>

            <form method="post" action="index.php?action=equipement_changer_etat">
                <input
                    type="hidden"
                    name="idEquipement"
                    value="<?= (int) $equipement['idEquipement'] ?>"
                >
                <input type="hidden" name="etat" value="HS">
                <input
                    type="hidden"
                    name="probleme"
                    value="Équipement non réparable - hors service"
                >

                <button type="submit" class="btn btn-danger">
                    Déclarer HS
                </button>
            </form>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <a href="index.php?action=equipements_technicien" class="btn">
                Retour à la liste
            </a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>