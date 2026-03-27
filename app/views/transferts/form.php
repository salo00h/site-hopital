<?php
declare(strict_types=1);

/*
==================================================
 VUE : Demander un transfert
==================================================
 Rôle :
 - Le médecin choisit d'abord le type de transfert
 - Si transfert inter-hôpitaux :
   afficher l'hôpital destinataire
 - Si transfert interne :
   afficher seulement le service destinataire

 Remarques d'organisation :
 - Ce fichier correspond uniquement à la couche View
 - Ce fichier doit rester simple et lisible
 - Pas de logique métier ici
 - Pas de CSS ici
 - Uniquement affichage et structure claire
==================================================
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

$idDossier = (int) ($_GET['idDossier'] ?? 0);
?>

<h1 class="page-title">Demander un transfert</h1>

<div class="card">

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Affichage simple des informations du dossier -->
    <p>
        <strong>Dossier :</strong>
        #<?= (int) $idDossier ?> —
        <?= htmlspecialchars(($dossier['nom'] ?? '') . ' ' . ($dossier['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <!-- Formulaire d'affichage : la vue présente les champs sans porter la logique métier -->
    <form class="form" method="post" action="index.php?action=transfert_create">
        <input type="hidden" name="idDossier" value="<?= (int) $idDossier ?>">

        <!-- Choix du type de transfert -->
        <p>
            <label for="typeTransfert">Type de transfert *</label>
            <select
                id="typeTransfert"
                name="typeTransfert"
                required
                onchange="toggleTransfertFields()"
            >
                <option value="">-- Choisir --</option>
                <option value="service">Transfert interne (service)</option>
                <option value="hopital">Transfert inter-hôpitaux</option>
            </select>
        </p>

        <!-- Bloc affiché uniquement si un hôpital destinataire doit être choisi -->
        <p id="blocHopital" style="display:none;">
            <label for="hopitalDestinataire">Hôpital destinataire *</label>
            <select id="hopitalDestinataire" name="hopitalDestinataire">
                <option value="">-- Choisir un hôpital --</option>

                <?php foreach (($hopitaux ?? []) as $h): ?>
                    <option value="<?= htmlspecialchars((string) $h['nom'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) $h['nom'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars((string) $h['ville'], ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <!-- Bloc du service destinataire -->
        <p id="blocService" style="display:none;">
            <label for="serviceDestinataire">Service destinataire *</label>
            <input id="serviceDestinataire" type="text" name="serviceDestinataire">
        </p>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Envoyer</button>
            <a
                class="btn btn-link"
                href="index.php?action=dossier_detail_medecin&id=<?= (int) $idDossier ?>"
            >
                Annuler
            </a>
        </div>
    </form>

    <div class="separator"></div>

    <h2 class="card-title">Historique transferts (patient)</h2>

    <?php if (empty($historique ?? [])): ?>
        <p>Aucun transfert.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date création</th>
                    <th>Statut</th>
                    <th>Hôpital destinataire</th>
                    <th>Service</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($historique ?? []) as $t): ?>
                    <tr>
                        <td><?= (int) ($t['idTransfer'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($t['dateCreation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($t['statutTransfer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($t['hopitalDestinataire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($t['serviceDestinataire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<script>
    function toggleTransfertFields() {
        const type = document.getElementById('typeTransfert').value;
        const blocHopital = document.getElementById('blocHopital');
        const blocService = document.getElementById('blocService');
        const hopital = document.getElementById('hopitalDestinataire');
        const service = document.getElementById('serviceDestinataire');

        // Réinitialiser l'affichage pour garder une vue claire et cohérente
        blocHopital.style.display = 'none';
        blocService.style.display = 'none';

        // Réinitialiser les validations côté interface
        hopital.required = false;
        service.required = false;

        // Désactiver les champs masqués pour éviter une soumission inutile
        hopital.disabled = true;
        service.disabled = true;

        // Cas : transfert inter-hôpitaux
        if (type === 'hopital') {
            blocHopital.style.display = 'block';
            blocService.style.display = 'block';

            hopital.required = true;
            service.required = true;

            hopital.disabled = false;
            service.disabled = false;
        }

        // Cas : transfert interne
        if (type === 'service') {
            blocService.style.display = 'block';

            service.required = true;
            service.disabled = false;

            // Vider l’hôpital car il n’est pas utilisé dans ce cas
            hopital.value = '';
        }
    }
</script>

<?php require_once APP_PATH . '/includes/footer.php'; ?>