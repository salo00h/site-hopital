<?php
/**
 * --------------------------------------------------------------------------
 * Rôle du médecin dans la modification du dossier
 * --------------------------------------------------------------------------
 * Le médecin ne modifie pas les informations administratives
 * du patient (nom, email, etc.).
 *
 * Il intervient uniquement sur la partie médicale :
 * - diagnostic
 * - traitements
 * - historique
 * - observations
 *
 * Cela respecte la séparation des responsabilités
 * entre infirmier d’accueil et médecin.
 */

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/sidebar.php';

$h = static function ($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<h1>Modifier dossier (médecin)</h1>

<form method="post" action="index.php?action=dossier_update_medecin">

<input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier'] ?>">

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