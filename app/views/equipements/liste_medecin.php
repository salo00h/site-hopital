<?php
require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/*
 * On récupère l'idDossier depuis l'URL.
 * S'il n'existe pas, on bloque la réservation (il faut d'abord sélectionner un dossier).
 */
$idDossier = (int)($_GET['idDossier'] ?? 0);
$dossierSelected = ($idDossier > 0);
?>

<h1 class="page-title">Liste des équipements</h1>

<div class="card">

    <!-- =========================
         Messages (flash)
         ========================= -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string)$_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string)$_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>


    <!-- =========================
         Avertissement si pas de dossier
         ========================= -->
    <?php if (!$dossierSelected): ?>
        <div class="alert alert-warning">
            Aucun dossier sélectionné. Ouvrez un dossier patient avant de réserver un équipement.
        </div>
    <?php else: ?>
        <p>
            <strong>Dossier concerné :</strong> #<?= (int)$idDossier ?>
        </p>
    <?php endif; ?>


    <!-- =========================
         Liste des équipements
         ========================= -->
    <?php if (empty($equipements)): ?>

        <div class="alert alert-warning">
            Aucun équipement trouvé.
        </div>

    <?php else: ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Numéro</th>
                    <th>Localisation</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($equipements as $eq): ?>

                    <?php
                    // Statut utilisé pour afficher le badge et activer/désactiver le bouton
                    $status = (string)($eq['etatEquipement'] ?? '');
                    ?>

                    <tr>
                        <td><?= htmlspecialchars((string)($eq['typeEquipement'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                        <td><?= (int)($eq['numeroEquipement'] ?? 0) ?></td>

                        <td>
                            <?= !empty($eq['localisation'])
                                ? htmlspecialchars((string)$eq['localisation'], ENT_QUOTES, 'UTF-8')
                                : '-' ?>
                        </td>

                        <!-- ===== Statut (badge) ===== -->
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= ucfirst(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')) ?>
                            </span>
                        </td>

                        <!-- ===== Actions ===== -->
                        <td>

                            <?php if ($status === 'disponible' && $dossierSelected): ?>

                                <!-- Disponible + dossier sélectionné => réservation possible -->
                                <a class="btn btn-primary"
                                   href="index.php?action=equipement_reserver_form&idEquipement=<?= (int)$eq['idEquipement'] ?>&idDossier=<?= (int)$idDossier ?>">
                                    Réserver
                                </a>

                            <?php elseif ($status === 'disponible' && !$dossierSelected): ?>

                                <!-- Disponible, mais pas de dossier sélectionné : aller choisir un dossier. -->
                                <a class="btn" href="index.php?action=dossiers_list">
                                  Sélectionner un dossier
                                </a>

                            <?php else: ?>

                                <!-- Équipement occupé / maintenance / HS / etc. -->
                                <button class="btn" disabled>
                                    Non disponible
                                </button>

                            <?php endif; ?>

                            <?php if ($status !== 'en_panne'): ?>
                            <a class="btn"
                              href="index.php?action=equipement_signaler_panne&id=<?= (int)$eq['idEquipement'] ?>">
                              Signaler panne
                            </a>
                            <?php else: ?>
                            <button class="btn" disabled>Déjà en panne</button>
                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>