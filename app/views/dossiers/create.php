<?php
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Vue uniquement : formulaire de création d'un dossier patient
|--------------------------------------------------------------------------
| Ce fichier est réservé à l'affichage.
| Il doit rester simple, lisible et bien structuré.
| Pas de logique métier ici.
| Pas de CSS ici.
| Uniquement la présentation des données et la structure HTML/PHP.
|--------------------------------------------------------------------------
*/

$old = static function (string $key, string $default = ''): string {
    return htmlspecialchars((string) ($_POST[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};

$nowLocal = date('Y-m-d\TH:i');

$niveauOld = $_POST['niveau'] ?? '1';

$delaisByNiveau = [
    '1' => '0',
    '2' => '10',
    '3' => '30',
    '4' => 'NonImmediat',
    '5' => 'NonImmediat',
];

$delaiOld = $_POST['delaiPriseCharge'] ?? ($delaisByNiveau[$niveauOld] ?? '0');
?>

<h1 class="page-title">Créer un dossier patient</h1>

<?php if (!empty($error)) : ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card">
    <form class="form" method="post" action="index.php?action=dossier_create">

        <?php
        /*
        ----------------------------------------------------------------------
        Section affichage : informations du patient
        Cette partie reste volontairement claire et directe pour la vue.
        Aucun traitement métier ne doit être déplacé ici.
        ----------------------------------------------------------------------
        */
        ?>
        <h3>Infos patient</h3>

        <p>
            <label>Nom *</label>
            <input type="text" name="nom" value="<?= $old('nom') ?>" required>
        </p>

        <p>
            <label>Prénom *</label>
            <input type="text" name="prenom" value="<?= $old('prenom') ?>" required>
        </p>

        <p>
            <label>Date naissance *</label>
            <input
                type="date"
                name="dateNaissance"
                value="<?= $old('dateNaissance') ?>"
                min="1900-01-01"
                required
            >
        </p>

        <p>
            <label>Genre *</label>
            <select name="genre" required>
                <option value="Homme" <?= $old('genre', 'Homme') === 'Homme' ? 'selected' : '' ?>>Homme</option>
                <option value="Femme" <?= $old('genre') === 'Femme' ? 'selected' : '' ?>>Femme</option>
                <option value="Autre" <?= $old('genre') === 'Autre' ? 'selected' : '' ?>>Autre</option>
            </select>
        </p>

        <p>
            <label>Adresse</label>
            <input type="text" name="adresse" value="<?= $old('adresse') ?>">
        </p>

        <p>
            <label>Téléphone</label>
            <input type="text" name="telephone" value="<?= $old('telephone') ?>">
        </p>

        <p>
            <label>Email</label>
            <input type="email" name="email" value="<?= $old('email') ?>">
        </p>

        <p>
            <label>Numéro carte vitale</label>
            <input
                type="text"
                name="numeroCarteVitale"
                value="<?= $old('numeroCarteVitale') ?>"
                required
            >
        </p>

        <p>
            <label>Mutuelle</label>
            <input type="text" name="mutuelle" value="<?= $old('mutuelle') ?>">
        </p>

        <div class="separator"></div>

        <?php
        /*
        ----------------------------------------------------------------------
        Section affichage : informations du dossier
        La vue présente les champs de saisie sans contenir de logique métier.
        Elle doit uniquement organiser l'interface de manière lisible.
        ----------------------------------------------------------------------
        */
        ?>
        <h3>Infos dossier</h3>

        <p>
            <label>Date et heure d’arrivée</label>
            <input
                type="datetime-local"
                name="dateAdmission"
                value="<?= $old('dateAdmission', $nowLocal) ?>"
            >
        </p>

        <p>
            <label>Date et heure de sortie</label>
            <input
                type="datetime-local"
                name="dateSortie"
                value="<?= $old('dateSortie') ?>"
            >
        </p>

        <!-- Champ caché d'affichage uniquement : le statut est fixé automatiquement -->
        <input type="hidden" name="statut" value="ouvert">

        <p>
            <label>Niveau *</label>
            <select name="niveau" id="niveau" required>
                <option value="1" <?= $old('niveau', '1') === '1' ? 'selected' : '' ?>>1</option>
                <option value="2" <?= $old('niveau') === '2' ? 'selected' : '' ?>>2</option>
                <option value="3" <?= $old('niveau') === '3' ? 'selected' : '' ?>>3</option>
                <option value="4" <?= $old('niveau') === '4' ? 'selected' : '' ?>>4</option>
                <option value="5" <?= $old('niveau') === '5' ? 'selected' : '' ?>>5</option>
            </select>
        </p>

        <p>
            <label>Délai prise en charge *</label>
            <input
                type="text"
                id="delaiPriseChargeDisplay"
                value="<?= htmlspecialchars($delaiOld, ENT_QUOTES, 'UTF-8') ?> minutes"
                readonly
            >
            <small>
                Niveau 1 = immédiat, Niveau 2 = 10 min, Niveau 3 = 30 min, Niveau 4 et 5 = non immédiat
            </small>
            <input
                type="hidden"
                name="delaiPriseCharge"
                id="delaiPriseCharge"
                value="<?= htmlspecialchars($delaiOld, ENT_QUOTES, 'UTF-8') ?>"
            >
        </p>

        <p>
            <label>État entrée</label>
            <!--
            Correction (avec assistance IA) : désactivation de l’autocomplétion du navigateur.
            Le champ "etat_entree" se remplissait automatiquement avec des valeurs suggérées
            (ex: "France"), non issues de la base de données.
            Après analyse, il s’agissait d’un comportement du navigateur.
            Nous avons modifié le nom du champ et utilisé autocomplete="new-password"
            pour éviter ce problème.
            -->
            <input
                type="text"
                name="etat_entree_patient"
                value="<?= $old('etat_entree') ?>"
                autocomplete="new-password"
                spellcheck="false"
            >
        </p>

        <p>
            <label>Diagnostic</label>
            <textarea name="diagnostic"><?= $old('diagnostic') ?></textarea>
        </p>

        <p>
            <label>Traitements</label>
            <textarea name="traitements"><?= $old('traitements') ?></textarea>
        </p>

        <p>
            <label>Historique médical</label>
            <textarea name="historiqueMedical"><?= $old('historiqueMedical') ?></textarea>
        </p>

        <p>
            <label>Antécédant</label>
            <textarea name="antecedant"><?= $old('antecedant') ?></textarea>
        </p>

        <!-- Valeur technique transmise au formulaire, sans logique métier dans la vue -->
        <input type="hidden" name="idHopital" value="1">

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Créer</button>
            <a class="btn" href="index.php?action=dossiers_list">Annuler</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const niveauSelect = document.getElementById('niveau');
    const delaiInput = document.getElementById('delaiPriseCharge');
    const delaiDisplay = document.getElementById('delaiPriseChargeDisplay');

    const delais = {
        '1': '0',
        '2': '10',
        '3': '30',
        '4': 'NonImmediat',
        '5': 'NonImmediat'
    };

    /*
    --------------------------------------------------------------------------
    Script d'interface uniquement :
    il sert à garder un affichage cohérent côté vue.
    Aucune logique métier métier ne doit être gérée ici.
    --------------------------------------------------------------------------
    */
    function updateDelai() {
        const niveau = niveauSelect.value;
        const delai = delais[niveau] || '0';

        delaiInput.value = delai;
        delaiDisplay.value = delai + ' minutes';
    }

    niveauSelect.addEventListener('change', updateDelai);
    updateDelai();
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>