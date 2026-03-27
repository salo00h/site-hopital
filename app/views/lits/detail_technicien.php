<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Vue : fiche détaillée d'un lit
|--------------------------------------------------------------------------
| Ce fichier est une vue MVC dédiée uniquement à l'affichage.
| Il doit rester simple, lisible et facile à maintenir.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement la structure d'affichage et les données transmises.
|--------------------------------------------------------------------------
*/
?>

<section class="tech-board">
    <h1 class="page-title">Fiche lit</h1>

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

    <div class="tech-detail-card">
        <p>
            <strong>N° Lit :</strong>
            <?= (int) $lit['numeroLit'] ?>
        </p>

        <p>
            <strong>Service :</strong>
            <?= htmlspecialchars((string) ($lit['serviceNom'] ?? '—')) ?>
        </p>

        <p>
            <strong>État :</strong>

            <?php
            /*
            ------------------------------------------------------------------
            Affichage visuel de l'état du lit :
            on prépare uniquement la classe CSS à afficher dans la vue.
            La logique métier ne doit pas être déplacée ici.
            ------------------------------------------------------------------
            */
            $etat = (string) ($lit['etatLit'] ?? '');

            $classe = match ($etat) {
                'disponible'  => 'badge-success',
                'en_panne'    => 'badge-danger',
                'maintenance' => 'badge-warning',
                'HS'          => 'badge-dark',
                default       => 'badge-secondary',
            };
            ?>

            <span class="badge <?= htmlspecialchars($classe) ?>">
                <?= htmlspecialchars($etat) ?>
            </span>
        </p>

        <hr>

        <h3>Dernière maintenance</h3>

        <?php if ($maintenance): ?>
            <p>
                <strong>Date début :</strong>
                <?= htmlspecialchars((string) $maintenance['dateDebutLit']) ?>
            </p>

            <p>
                <strong>Date fin :</strong>
                <?= htmlspecialchars((string) ($maintenance['dateFinLit'] ?? 'En cours')) ?>
            </p>

            <p>
                <strong>Problème :</strong>
                <?= htmlspecialchars((string) $maintenance['problemeLit']) ?>
            </p>
        <?php else: ?>
            <p>Aucune maintenance enregistrée.</p>
        <?php endif; ?>

        <hr>

        <?php if (($lit['etatLit'] ?? '') === 'en_panne'): ?>
            <form method="post" action="index.php?action=lit_changer_etat">
                <input type="hidden" name="idLit" value="<?= (int) $lit['idLit'] ?>">
                <input type="hidden" name="etat" value="maintenance">

                <label>Diagnostic / problème</label>
                <textarea name="probleme" rows="4" required>Diagnostic du lit n°<?= (int) $lit['numeroLit'] ?></textarea>

                <button type="submit" class="btn btn-warning">
                    Passer en maintenance
                </button>
            </form>
        <?php endif; ?>

        <?php if (($lit['etatLit'] ?? '') === 'maintenance'): ?>
            <form
                method="post"
                action="index.php?action=lit_changer_etat"
                style="margin-bottom:10px;"
            >
                <input type="hidden" name="idLit" value="<?= (int) $lit['idLit'] ?>">
                <input type="hidden" name="etat" value="disponible">
                <input type="hidden" name="probleme" value="Maintenance terminée - lit réparé">

                <button type="submit" class="btn btn-success">
                    Mettre disponible
                </button>
            </form>

            <form method="post" action="index.php?action=lit_changer_etat">
                <input type="hidden" name="idLit" value="<?= (int) $lit['idLit'] ?>">
                <input type="hidden" name="etat" value="HS">
                <input type="hidden" name="probleme" value="Lit non réparable - hors service">

                <button type="submit" class="btn btn-danger">
                    Déclarer HS
                </button>
            </form>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <a href="index.php?action=lits_technicien" class="btn">
                Retour à la liste
            </a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>