<?php
/*
|--------------------------------------------------------------------------
| Vue : réservation d’un lit
|--------------------------------------------------------------------------
| Ce fichier correspond uniquement à l’affichage dans l’architecture MVC.
| Il doit rester simple, lisible et bien organisé.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement affichage et structure claire.
|--------------------------------------------------------------------------
| Cette vue permet de réserver un lit disponible pour un dossier patient.
| Le nom du service est affiché dans la liste pour mieux contextualiser
| chaque lit proposé.
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

$minutes = 0;

switch ($delai ?? '0') {
    case '10':
        $minutes = 10;
        break;

    case '30':
        $minutes = 30;
        break;

    case 'NonImmediat':
        $minutes = 120;
        break;

    case '0':
        $minutes = 1;
        break;

    default:
        $minutes = 1;
}

$dateFin = date('Y-m-d\TH:i', time() + ($minutes * 60));
?>

<h1 class="page-title">Réserver un lit</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($availableLits)): ?>
    <div class="card">
        <p>Aucun lit disponible pour le moment.</p>
        <a class="btn" href="index.php?action=lits_dashboard">Retour au dashboard lits</a>
    </div>
<?php else: ?>
    <div class="card">
        <p class="card-subtitle">
            La réservation permet de bloquer un lit pour un patient.<br>
            Le lit sera réellement occupé lorsque l’infirmier confirme l’installation du patient.
        </p>

        <?php if (($delai ?? '') === '0'): ?>
            <div class="alert alert-info">
                Niveau 1 : réservation immédiate prioritaire.
                L’installation du patient sera confirmée ensuite par l’infirmier.
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="index.php?action=lit_reserver">
            <input type="hidden" name="idDossier" value="<?= (int) $idDossier ?>">

            <p>
                <label>Lit disponible</label>
                <select name="idLit" required>
                    <option value="">-- Choisir --</option>

                    <?php foreach ($availableLits as $lit): ?>
                        <option value="<?= (int) $lit['idLit'] ?>">
                            Lit #<?= (int) $lit['numeroLit'] ?> — <?= htmlspecialchars($lit['serviceNom'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label>Date début de réservation</label>
                <input
                    type="datetime-local"
                    name="dateDebut"
                    value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </p>

            <?php if (($delai ?? '') !== '0'): ?>
                <p>
                    <label>Date fin prévue</label>
                    <input
                        type="datetime-local"
                        name="dateFin"
                        value="<?= htmlspecialchars($dateFin, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </p>
            <?php endif; ?>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Réserver</button>
                <a class="btn" href="index.php?action=dossier_detail&id=<?= (int) $idDossier ?>">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>