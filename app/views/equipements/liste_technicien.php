<?php
/*
|--------------------------------------------------------------------------
| Vue : consultation des fiches d’équipement
|--------------------------------------------------------------------------
| Ce fichier appartient à la couche View en MVC.
| Il doit rester simple, lisible et dédié à l’affichage.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement affichage et structure claire.
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<section class="tech-board">
    <h1 class="page-title">Consulter fiche d’équipement</h1>

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

    <?php
    /*
    |--------------------------------------------------------------------------
    | Tableau de consultation
    |--------------------------------------------------------------------------
    | Cette vue affiche la liste des équipements de manière claire.
    | Les données sont déjà préparées avant l’affichage.
    |--------------------------------------------------------------------------
    */
    ?>
    <div class="tech-table-card">
        <table class="tech-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>N°</th>
                    <th>Service</th>
                    <th>État</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($equipements as $equipement): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $equipement['typeEquipement']) ?>
                        </td>

                        <td>
                            <?= (int) $equipement['numeroEquipement'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars((string) ($equipement['serviceNom'] ?? '—')) ?>
                        </td>

                        <td>
                            <?php
                            /*
                            |--------------------------------------------------------------------------
                            | Affichage de l’état sous forme de badge
                            |--------------------------------------------------------------------------
                            | On garde ici uniquement une correspondance d’affichage
                            | pour rendre la lecture plus claire dans la vue.
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
                        </td>

                        <td>
                            <a
                                class="btn btn-sm"
                                href="index.php?action=equipement_detail_technicien&idEquipement=<?= (int) $equipement['idEquipement'] ?>"
                            >
                                Voir fiche
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>