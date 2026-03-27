<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Vue : changement d'état des lits
|--------------------------------------------------------------------------
| Ce fichier correspond uniquement à l'affichage dans le cadre MVC.
| Il doit rester simple, lisible et bien structuré.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement l'affichage des données et des actions disponibles.
|--------------------------------------------------------------------------
*/

$h = static function (mixed $v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1 class="page-title">Changer état lit</h1>

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

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Numéro lit</th>
                <th>État actuel</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($lits as $lit): ?>
                <?php $etat = (string) ($lit['etatLit'] ?? ''); ?>

                <tr>
                    <td><?= (int) ($lit['numeroLit'] ?? 0) ?></td>
                    <td><?= $h($etat) ?></td>
                    <td>
                        <?php if ($etat === 'reserve'): ?>
                            <a
                                class="btn btn-primary"
                                href="index.php?action=lit_changer_etat_infirmier&idLit=<?= (int) $lit['idLit'] ?>&etat=occupe"
                            >
                                Passer à occupé
                            </a>

                        <?php elseif ($etat === 'occupe'): ?>
                            <a
                                class="btn btn-primary"
                                href="index.php?action=lit_changer_etat_infirmier&idLit=<?= (int) $lit['idLit'] ?>&etat=disponible"
                            >
                                Libérer le lit
                            </a>

                            <a
                                class="btn btn-danger"
                                href="index.php?action=lit_changer_etat_infirmier&idLit=<?= (int) $lit['idLit'] ?>&etat=en_panne"
                            >
                                Signaler panne
                            </a>

                        <?php elseif ($etat === 'disponible'): ?>
                            <a
                                class="btn btn-danger"
                                href="index.php?action=lit_changer_etat_infirmier&idLit=<?= (int) $lit['idLit'] ?>&etat=en_panne"
                            >
                                Signaler panne
                            </a>

                        <?php else: ?>
                            <button class="btn" disabled>Aucune action</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>