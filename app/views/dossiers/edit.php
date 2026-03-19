<?php require __DIR__ . '/../../includes/header.php'; ?>
<?php require __DIR__ . '/../../includes/sidebar.php'; ?>

<?php
$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$formatDateTimeLocal = static function (?string $v): string {
    if (empty($v)) {
        return '';
    }
    $ts = strtotime($v);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $ts);
};
?>

<h1 class="page-title">Modifier dossier #<?= (int)$dossier['idDossier']; ?></h1>

<?php if (!empty($error)) : ?>
  <div class="alert alert-danger"><?= $h($error); ?></div>
<?php endif; ?>

<div class="card">
  <form class="form" method="post" action="index.php?action=dossier_update">
    <input type="hidden" name="idDossier" value="<?= (int)$dossier['idDossier']; ?>">
    <input type="hidden" name="idPatient" value="<?= (int)$dossier['idPatient']; ?>">

    <h3>Patient</h3>

    <p>
      <label>Nom</label>
      <input name="nom" value="<?= $h($dossier['nom']) ?>" required>
    </p>

    <p>
      <label>Prénom</label>
      <input name="prenom" value="<?= $h($dossier['prenom']) ?>" required>
    </p>

    <p>
      <label>Date naissance</label>
      <input
        type="date"
        name="dateNaissance"
        value="<?= $h($dossier['dateNaissance']) ?>"
        min="1900-01-01"
        
        required
      >
    </p>

    <p>
      <label>Genre</label>
      <select name="genre">
        <option value="Homme" <?= (($dossier['genre'] ?? '') === 'Homme') ? 'selected' : '' ?>>Homme</option>
        <option value="Femme" <?= (($dossier['genre'] ?? '') === 'Femme') ? 'selected' : '' ?>>Femme</option>
        <option value="Autre" <?= (($dossier['genre'] ?? '') === 'Autre') ? 'selected' : '' ?>>Autre</option>
      </select>
    </p>

    <p>
      <label>Adresse</label>
      <input name="adresse" value="<?= $h($dossier['adresse'] ?? '') ?>">
    </p>

    <p>
      <label>Téléphone</label>
      <input name="telephone" value="<?= $h($dossier['telephone'] ?? '') ?>">
    </p>

    <p>
      <label>Email</label>
      <input type="email" name="email" value="<?= $h($dossier['email'] ?? '') ?>">
    </p>

    <p>
      <label>Numéro carte vitale</label>
      <input name="numeroCarteVitale" value="<?= $h($dossier['numeroCarteVitale'] ?? '') ?>">
    </p>

    <p>
      <label>Mutuelle</label>
      <input name="mutuelle" value="<?= $h($dossier['mutuelle'] ?? '') ?>">
    </p>

    <div class="separator"></div>

    <h3>Dossier</h3>

    <p>
      <label>Date et heure d’arrivée</label>
      <input
        type="datetime-local"
        name="dateAdmission"
        value="<?= $formatDateTimeLocal($dossier['dateAdmission'] ?? null) ?>"
      >
    </p>

    <p>
      <label>Date et heure de sortie</label>
      <input
        type="datetime-local"
        name="dateSortie"
        value="<?= $formatDateTimeLocal($dossier['dateSortie'] ?? null) ?>"
      >
    </p>

    <p>
      <label>Statut</label>
      <select name="statut">
        <option value="ouvert" <?= (($dossier['statut'] ?? '') === 'ouvert') ? 'selected' : '' ?>>ouvert</option>
        <option value="attente_consultation" <?= (($dossier['statut'] ?? '') === 'attente_consultation') ? 'selected' : '' ?>>attente_consultation</option>
        <option value="consultation" <?= (($dossier['statut'] ?? '') === 'consultation') ? 'selected' : '' ?>>consultation</option>
        <option value="attente_examen" <?= (($dossier['statut'] ?? '') === 'attente_examen') ? 'selected' : '' ?>>attente_examen</option>
        <option value="attente_resultat" <?= (($dossier['statut'] ?? '') === 'attente_resultat') ? 'selected' : '' ?>>attente_resultat</option>
        <option value="transfert" <?= (($dossier['statut'] ?? '') === 'transfert') ? 'selected' : '' ?>>transfert</option>
        <option value="ferme" <?= (($dossier['statut'] ?? '') === 'ferme') ? 'selected' : '' ?>>ferme</option>
      </select>
    </p>

    <p>
      <label>Niveau de priorité</label>
      <select name="niveau">
        <option value="1" <?= (($dossier['niveau'] ?? '') == '1') ? 'selected' : '' ?>>1</option>
        <option value="2" <?= (($dossier['niveau'] ?? '') == '2') ? 'selected' : '' ?>>2</option>
        <option value="3" <?= (($dossier['niveau'] ?? '') == '3') ? 'selected' : '' ?>>3</option>
        <option value="4" <?= (($dossier['niveau'] ?? '') == '4') ? 'selected' : '' ?>>4</option>
        <option value="5" <?= (($dossier['niveau'] ?? '') == '5') ? 'selected' : '' ?>>5</option>
      </select>
    </p>

    <p>
      <label>Délai prise en charge</label>
      <select name="delaiPriseCharge">
        <option value="0" <?= (($dossier['delaiPriseCharge'] ?? '') === '0') ? 'selected' : '' ?>>0</option>
        <option value="10" <?= (($dossier['delaiPriseCharge'] ?? '') === '10') ? 'selected' : '' ?>>10</option>
        <option value="30" <?= (($dossier['delaiPriseCharge'] ?? '') === '30') ? 'selected' : '' ?>>30</option>
        <option value="NonImmediat" <?= (($dossier['delaiPriseCharge'] ?? '') === 'NonImmediat') ? 'selected' : '' ?>>NonImmediat</option>
      </select>
    </p>

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

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Enregistrer</button>
      <a class="btn" href="index.php?action=dossier_detail&id=<?= (int)$dossier['idDossier']; ?>">Annuler</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>