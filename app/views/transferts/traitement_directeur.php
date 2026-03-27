<?php
declare(strict_types=1);

/*
==================================================
 VUE : Traitement des transferts - Directeur
==================================================
 Rôle :
 - Afficher les demandes de transfert en attente
 - Permettre au directeur de valider ou refuser

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
    <h1 class="page-title">Traitement des demandes de transfert</h1>

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
            <p>Aucune demande de transfert en attente.</p>
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
                        <th>Décision</th>
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
                            <td>
                                <!-- La vue affiche les actions disponibles sans porter la logique de traitement -->
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <form method="post" action="index.php?action=transfert_update_statut">
                                        <input
                                            type="hidden"
                                            name="idTransfer"
                                            value="<?= (int) ($transfert['idTransfer'] ?? 0) ?>"
                                        >
                                        <input type="hidden" name="statut" value="accepte">
                                        <button type="submit" class="btn-primary">Valider</button>
                                    </form>

                                    <form method="post" action="index.php?action=transfert_update_statut">
                                        <input
                                            type="hidden"
                                            name="idTransfer"
                                            value="<?= (int) ($transfert['idTransfer'] ?? 0) ?>"
                                        >
                                        <input type="hidden" name="statut" value="refuse">
                                        <button type="submit" class="btn-danger">Refuser</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>