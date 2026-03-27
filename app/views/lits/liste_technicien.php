<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Vue : consultation des fiches lits
|--------------------------------------------------------------------------
| Ce fichier correspond uniquement à l'affichage dans l'architecture MVC.
| Il doit rester simple, lisible et facile à maintenir.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement l'affichage des informations et de la structure HTML.
|--------------------------------------------------------------------------
*/
?>

<section class="tech-board">
    <h1 class="page-title">Consulter fiche lit</h1>

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

    <div class="tech-table-card">
        <table class="tech-table">
            <thead>
                <tr>
                    <th>N° Lit</th>
                    <th>Service</th>
                    <th>État</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($lits as $lit): ?>
                    <tr>
                        <td><?= (int) $lit['numeroLit'] ?></td>
                        <td><?= htmlspecialchars((string) ($lit['serviceNom'] ?? '—')) ?></td>
                        <td>
                            <?php
                            /*
                            --------------------------------------------------
                            Préparation de la classe visuelle du badge d'état.
                            Cette partie reste limitée à l'affichage.
                            --------------------------------------------------
                            */
                            $etat = (string) $lit['etatLit'];

                            $classe = match ($etat) {
                                'disponible'  => 'badge-success',
                                'en_panne'    => 'badge-danger',
                                'maintenance' => 'badge-warning',
                                'HS'          => 'badge-dark',
                                default       => 'badge-secondary',
                            };
                            ?>

                            <span class="badge <?= $classe ?>">
                                <?= htmlspecialchars($etat) ?>
                            </span>
                        </td>
                        <td>
                            <a
                                class="btn btn-sm"
                                href="index.php?action=lit_detail_technicien&idLit=<?= (int) $lit['idLit'] ?>"
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