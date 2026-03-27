<?php
/*
|--------------------------------------------------------------------------
| Vue : réservation d’un équipement
|--------------------------------------------------------------------------
| Ce fichier appartient uniquement à la couche View du MVC.
| Il doit rester simple, lisible et centré sur l’affichage.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement affichage et structure claire.
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Détection du rôle connecté
|--------------------------------------------------------------------------
| La vue adapte seulement les routes d’affichage selon le rôle courant.
| On conserve ici une structure claire sans toucher à la logique métier.
|--------------------------------------------------------------------------
*/
$role = $_SESSION['user']['role'] ?? '';
$isInfirmier = ($role === 'INFIRMIER');

/*
|--------------------------------------------------------------------------
| Détermination des actions selon le rôle
|--------------------------------------------------------------------------
| Cette préparation sert uniquement à construire les liens et formulaires
| affichés dans la vue.
|--------------------------------------------------------------------------
*/
$listAction    = $isInfirmier ? 'equipements_list_infirmier' : 'equipements_list_medecin';
$reserveAction = $isInfirmier ? 'equipement_reserver_infirmier' : 'equipement_reserver';
?>

<h1 class="page-title">Réserver un équipement</h1>

<div class="card form">

    <?php
    /*
    |--------------------------------------------------------------------------
    | Affichage du dossier concerné
    |--------------------------------------------------------------------------
    | Ce bloc présente uniquement le contexte de réservation.
    |--------------------------------------------------------------------------
    */
    ?>
    <p>
        <strong>Dossier concerné :</strong> #<?= (int) $idDossier ?>
    </p>

    <div class="separator"></div>

    <?php
    /*
    |--------------------------------------------------------------------------
    | Informations sur l’équipement sélectionné
    |--------------------------------------------------------------------------
    | La vue affiche les données reçues sans ajouter de traitement métier.
    |--------------------------------------------------------------------------
    */
    ?>
    <p>
        <strong>Équipement sélectionné :</strong>
        <?= htmlspecialchars($equipement['typeEquipement']) ?> - N° <?= (int) $equipement['numeroEquipement'] ?>
        <?php if (!empty($equipement['localisation'])): ?>
            (<?= htmlspecialchars($equipement['localisation']) ?>)
        <?php endif; ?>
    </p>

    <div class="separator"></div>

    <?php
    /*
    |--------------------------------------------------------------------------
    | Affichage conditionnel selon la disponibilité
    |--------------------------------------------------------------------------
    | La vue se limite à afficher soit un message d’indisponibilité,
    | soit le formulaire de confirmation.
    |--------------------------------------------------------------------------
    */
    ?>
    <?php if ($equipement['etatEquipement'] !== 'disponible'): ?>

        <div class="alert alert-warning">
            Cet équipement n'est pas disponible. Impossible de le réserver.
        </div>

        <div class="form-actions">
            <a class="btn" href="index.php?action=<?= $listAction ?>&idDossier=<?= (int) $idDossier ?>">
                ← Retour
            </a>

            <a
                class="btn btn-primary"
                href="index.php?action=equipement_signaler_panne&id=<?= (int) $equipement['idEquipement'] ?>"
            >
                Signaler en panne
            </a>
        </div>

    <?php else: ?>

        <form method="post" action="index.php?action=<?= $reserveAction ?>">
            <?php
            /*
            |--------------------------------------------------------------------------
            | Identifiants transmis par le formulaire
            |--------------------------------------------------------------------------
            | On garde ici uniquement les champs nécessaires à l’action
            | sans alourdir la vue.
            |--------------------------------------------------------------------------
            */
            ?>
            <input type="hidden" name="idEquipement" value="<?= (int) $equipement['idEquipement'] ?>">
            <input type="hidden" name="idDossier" value="<?= (int) $idDossier ?>">

            <div class="form-actions">
                <a class="btn" href="index.php?action=<?= $listAction ?>&idDossier=<?= (int) $idDossier ?>">
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