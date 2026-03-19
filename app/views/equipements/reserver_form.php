<?php
require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/* Détection du rôle connecté */
$role = $_SESSION['user']['role'] ?? '';
$isInfirmier = ($role === 'INFIRMIER');

/* Détermination des actions selon le rôle */
$listAction = $isInfirmier ? 'equipements_list_infirmier' : 'equipements_list_medecin';
$reserveAction = $isInfirmier ? 'equipement_reserver_infirmier' : 'equipement_reserver';
?>

<h1 class="page-title">Réserver un équipement</h1>

<div class="card form">

    <!-- Affichage du dossier lié à la réservation -->
    <p><strong>Dossier concerné :</strong> #<?= (int)$idDossier ?></p>

    <div class="separator"></div>

    <!-- Informations de l'équipement sélectionné -->
    <p>
        <strong>Équipement sélectionné :</strong>
        <?= htmlspecialchars($equipement['typeEquipement']) ?> - N° <?= (int)$equipement['numeroEquipement'] ?>
        <?php if (!empty($equipement['localisation'])): ?>
            (<?= htmlspecialchars($equipement['localisation']) ?>)
        <?php endif; ?>
    </p>

    <div class="separator"></div>

    <?php if ($equipement['etatEquipement'] !== 'disponible'): ?>

        <!-- Message si l'équipement n'est pas disponible -->
        <div class="alert alert-warning">
            Cet équipement n'est pas disponible. Impossible de le réserver.
        </div>

        <div class="form-actions">

            <!-- Retour vers la liste des équipements selon le rôle -->
            <a class="btn" href="index.php?action=<?= $listAction ?>&idDossier=<?= (int)$idDossier ?>">
                ← Retour
            </a>

            <!-- Possibilité de signaler une panne -->
            <a class="btn btn-primary" href="index.php?action=equipement_signaler_panne&id=<?= (int)$equipement['idEquipement'] ?>">
                Signaler en panne
            </a>

        </div>

    <?php else: ?>

        <!-- Formulaire de réservation de l'équipement -->
        <form method="post" action="index.php?action=<?= $reserveAction ?>">

            <!-- Transmission des identifiants nécessaires -->
            <input type="hidden" name="idEquipement" value="<?= (int)$equipement['idEquipement'] ?>">
            <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

            <div class="form-actions">

                <!-- Retour vers la liste -->
                <a class="btn" href="index.php?action=<?= $listAction ?>&idDossier=<?= (int)$idDossier ?>">
                    ← Retour
                </a>

                <!-- Confirmation de la réservation -->
                <button class="btn btn-primary" type="submit">
                    Confirmer la réservation
                </button>

            </div>
        </form>

    <?php endif; ?>

</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>