<?php
require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';
?>

<h1 class="page-title">Réserver un équipement</h1>

<div class="card form">

    <p><strong>Dossier concerné :</strong> #<?= (int)$idDossier ?></p>

    <div class="separator"></div>

    <p>
        <strong>Équipement sélectionné :</strong>
        <?= htmlspecialchars($equipement['typeEquipement']) ?> - N° <?= (int)$equipement['numeroEquipement'] ?>
        <?php if (!empty($equipement['localisation'])): ?>
            (<?= htmlspecialchars($equipement['localisation']) ?>)
        <?php endif; ?>
    </p>

    <div class="separator"></div>

    <?php if ($equipement['etatEquipement'] !== 'disponible'): ?>

        <div class="alert alert-warning">
            Cet équipement n'est pas disponible. Impossible de le réserver.
        </div>

        <div class="form-actions">
            <a class="btn" href="index.php?action=equipements_list_medecin&idDossier=<?= (int)$idDossier ?>">
                ← Retour
            </a>

            <a class="btn btn-primary" href="index.php?action=equipement_signaler_panne&id=<?= (int)$equipement['idEquipement'] ?>">
                Signaler en panne
            </a>
        </div>

    <?php else: ?>
        <!-- Transmission de l'idDossier pour lier l'équipement au dossier lors de la réservation -->
        <form method="post" action="index.php?action=equipement_reserver">
            <input type="hidden" name="idEquipement" value="<?= (int)$equipement['idEquipement'] ?>">
            <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

            <div class="form-actions">
                <a class="btn" href="index.php?action=equipements_list_medecin&idDossier=<?= (int)$idDossier ?>">
                    ← Retour
                </a>

                <button class="btn btn-primary" type="submit">
                    Confirmer la réservation
                </button>
            </div>
        </form>

    <?php endif; ?>

</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>