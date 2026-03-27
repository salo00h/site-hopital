<?php
declare(strict_types=1);

/*
==================================================
 VUE : Historique des transferts
==================================================
 Rôle :
 - Afficher l'historique des transferts
 - Vue simple pour le directeur

 Remarques d'organisation :
 - Ce fichier correspond uniquement à la couche View
 - Ce fichier doit rester simple et lisible
 - Pas de logique métier ici
 - Pas de CSS ici
 - Uniquement affichage et structure claire
==================================================
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';
?>

<section class="tech-board">
    <h1 class="page-title">Historique des transferts</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="tech-table-card">
        <?php if (empty($transferts)): ?>
            <p>Aucun transfert enregistré.</p>
        <?php else: ?>
            <table class="tech-table">
                <thead>
                    <tr>
                        <th>ID transfert</th>
                        <th>ID patient</th>
                        <th>Hôpital destinataire</th>
                        <th>Service destinataire</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transferts as $transfert): ?>
                        <tr>
                            <td><?= (int) ($transfert['idTransfer'] ?? 0) ?></td>
                            <td>P-<?= (int) ($transfert['idPatient'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($transfert['hopitalDestinataire'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($transfert['serviceDestinataire'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($transfert['dateCreation'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($transfert['statutTransfer'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>