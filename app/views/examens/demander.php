<?php
declare(strict_types=1);

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

/*
  Vue : Demander un examen
  Rôle : afficher un formulaire simple pour le médecin
*/

$idDossier = (int)($_GET['idDossier'] ?? 0);
if ($idDossier <= 0) {
    echo "<p>Dossier invalide.</p>";
    require_once APP_PATH . '/includes/footer.php';
    exit;
}
?>
<div class="container">
    <h1>Demander un examen</h1>

    <form method="post" action="index.php?action=examen_demander">
        <input type="hidden" name="idDossier" value="<?= (int)$idDossier ?>">

        <label for="type">Type d'examen</label>
        <select name="type" id="type" required>
            <option value="">-- Choisir --</option>
            <option value="Radiologie">Radiologie</option>
            <option value="Imagerie">Imagerie</option>
            <option value="Analyse">Analyse</option>
            <option value="Scanner">Scanner</option>
            <option value="IRM">IRM</option>
        </select>

        <label for="note">Note du médecin (optionnel)</label>
        <textarea name="note" id="note" rows="4" placeholder="Ex : douleur thoracique, urgence..."></textarea>

        <button type="submit">Envoyer la demande</button>
        <a class="btn" href="index.php?action=dossier_detail_medecin&id=<?= (int)$idDossier ?>">Retour</a>
    </form>
</div>

<?php require_once APP_PATH . '/includes/footer.php'; ?>