<?php
require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

$idDossier = (int)($_GET['idDossier'] ?? 0);
$dossierSelected = ($idDossier > 0);

/*
|------------------------------------------------------------
| Déterminer le rôle courant pour adapter les routes
|------------------------------------------------------------
*/
$isInfirmier = ($_SESSION['user']['role'] ?? '') === 'INFIRMIER';
$isMedecin   = ($_SESSION['user']['role'] ?? '') === 'MEDECIN';

$listAction           = $isInfirmier ? 'equipements_list_infirmier' : 'equipements_list_medecin';
$reserverFormAction   = $isInfirmier ? 'equipement_reserver_form_infirmier' : 'equipement_reserver_form';
$utiliserAction       = $isInfirmier ? 'equipement_utiliser' : 'equipement_utiliser_medecin';
$libererAction        = $isInfirmier ? 'equipement_liberer' : 'equipement_liberer_medecin';
$signalerPanneAction  = $isInfirmier ? 'equipement_signaler_panne_infirmier' : 'equipement_signaler_panne';

$detailAction         = $isInfirmier ? 'dossier_detail' : 'dossier_detail_medecin';
?>

<h1 class="page-title">Équipements</h1>

<div class="card">

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

    <?php if (!$dossierSelected): ?>
        <div class="alert alert-warning">
            Aucun dossier sélectionné. Ouvrez un dossier patient avant de réserver un équipement.
        </div>
    <?php else: ?>
        <p>
            <strong>Dossier concerné :</strong> #<?= (int)$idDossier ?>
        </p>
    <?php endif; ?>

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
                    <th>Dossier / Patient</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($equipements as $eq): ?>
                    <?php $status = (string)($eq['etatEquipement'] ?? ''); ?>

                    <tr>
                        <td><?= htmlspecialchars((string)($eq['typeEquipement'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($eq['numeroEquipement'] ?? 0) ?></td>
                        <td>
                            <?= !empty($eq['localisation'])
                                ? htmlspecialchars((string)$eq['localisation'], ENT_QUOTES, 'UTF-8')
                                : '-' ?>
                        </td>

                        <td>
                            <?php if ($status === 'disponible'): ?>
                                <span class="badge badge-success">Disponible</span>
                            <?php elseif ($status === 'reserve'): ?>
                                <span class="badge badge-warning">Réservé</span>
                            <?php elseif ($status === 'occupe'): ?>
                                <span class="badge badge-danger">Occupé</span>
                            <?php elseif ($status === 'en_panne'): ?>
                                <span class="badge badge-dark">En panne</span>
                            <?php elseif ($status === 'maintenance'): ?>
                                <span class="badge badge-secondary">Maintenance</span>
                            <?php else: ?>
                                <span class="badge"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (in_array($status, ['reserve', 'occupe'], true) && !empty($eq['idDossier'])): ?>
                                Dossier #<?= (int)$eq['idDossier'] ?>
                                <?php if (!empty($eq['nom']) || !empty($eq['prenom'])): ?>
                                    - <?= htmlspecialchars(trim(($eq['nom'] ?? '') . ' ' . ($eq['prenom'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($status === 'disponible'): ?>

                                <?php if ($dossierSelected): ?>
                                    <a class="btn btn-primary"
                                       href="index.php?action=<?= $reserverFormAction ?>&idEquipement=<?= (int)$eq['idEquipement'] ?>&idDossier=<?= (int)$idDossier ?>">
                                        Réserver
                                    </a>
                                <?php else: ?>
                                    <a class="btn" href="index.php?action=dossiers_list">
                                        Sélectionner un dossier
                                    </a>
                                <?php endif; ?>

                            <?php elseif ($status === 'reserve'): ?>

                                <?php if (!empty($eq['idDossier'])): ?>
                                    <a class="btn btn-warning"
                                       href="index.php?action=<?= $utiliserAction ?>&idEquipement=<?= (int)$eq['idEquipement'] ?>&idDossier=<?= (int)$eq['idDossier'] ?>">
                                        Utiliser
                                    </a>
                                <?php else: ?>
                                    <button class="btn" disabled>Non disponible</button>
                                <?php endif; ?>

                            <?php elseif ($status === 'occupe'): ?>

                                <?php if (!empty($eq['idDossier'])): ?>
                                    <a class="btn btn-danger"
                                       href="index.php?action=<?= $libererAction ?>&idEquipement=<?= (int)$eq['idEquipement'] ?>&idDossier=<?= (int)$eq['idDossier'] ?>">
                                        Libérer
                                    </a>
                                <?php else: ?>
                                    <button class="btn" disabled>Non disponible</button>
                                <?php endif; ?>

                            <?php else: ?>
                                <button class="btn" disabled>Non disponible</button>
                            <?php endif; ?>

                            <?php if (in_array($status, ['disponible', 'reserve', 'occupe'], true)): ?>
                                <a class="btn"
                                   href="index.php?action=<?= $signalerPanneAction ?>&id=<?= (int)$eq['idEquipement'] ?><?php if ($idDossier > 0): ?>&idDossier=<?= (int)$idDossier ?><?php endif; ?>">
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