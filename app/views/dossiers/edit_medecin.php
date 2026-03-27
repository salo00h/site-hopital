<?php
/**
 * --------------------------------------------------------------------------
 * Vue : modification du dossier par le médecin
 * --------------------------------------------------------------------------
 * Ce fichier correspond uniquement à l'affichage du formulaire.
 * Il doit rester simple, lisible et centré sur la partie interface.
 *
 * Rappel de responsabilité :
 * Le médecin ne modifie pas les informations administratives
 * du patient (nom, email, etc.).
 *
 * Il intervient uniquement sur la partie médicale :
 * - état d'entrée
 * - diagnostic
 * - traitements
 * - historique médical
 * - antécédant
 *
 * Cela respecte la séparation des responsabilités
 * entre infirmier d’accueil et médecin.
 *
 * Règles de cette vue :
 * - affichage uniquement
 * - pas de logique métier ici
 * - pas de CSS ici
 * - structure HTML/PHP claire et maintenable
 * --------------------------------------------------------------------------
 */

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

/*
|--------------------------------------------------------------------------
| Fonction d'échappement pour l'affichage
|--------------------------------------------------------------------------
| Cette fonction sert uniquement à sécuriser les données affichées
| dans le formulaire de la vue.
|--------------------------------------------------------------------------
*/
$h = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1>Modifier dossier (médecin)</h1>

<form method="post" action="index.php?action=dossier_update_medecin">
    <input type="hidden" name="idDossier" value="<?= (int) $dossier['idDossier'] ?>">

    <?php
    /*
    --------------------------------------------------------------------------
    Partie médicale uniquement
    --------------------------------------------------------------------------
    Cette vue ne présente que les champs que le médecin est autorisé
    à modifier. On garde donc un formulaire court, clair et ciblé.
    --------------------------------------------------------------------------
    */
    ?>
    <p>
        <label>État entrée</label>
        <input name="etat_entree" value="<?= $h($dossier['etat_entree'] ?? '') ?>">
    </p>

    <p>
        <label>Diagnostic</label>
        <textarea name="diagnostic"><?= $h($dossier['diagnostic'] ?? '') ?></textarea>
    </p>

    <p>
        <label>Traitements</label>
        <textarea name="traitements"><?= $h($dossier['traitements'] ?? '') ?></textarea>
    </p>

    <p>
        <label>Historique médical</label>
        <textarea name="historiqueMedical"><?= $h($dossier['historiqueMedical'] ?? '') ?></textarea>
    </p>

    <p>
        <label>Antécédant</label>
        <textarea name="antecedant"><?= $h($dossier['antecedant'] ?? '') ?></textarea>
    </p>

    <button type="submit">Enregistrer</button>
</form>