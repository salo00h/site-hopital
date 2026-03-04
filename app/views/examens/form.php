<?php
declare(strict_types=1);

/*
==================================================
  VUE : Formulaire demande d'examen (Médecin)
==================================================
  - Page مستقلة
  - Permet اختيار typeExamen من قائمة + note
  - POST vers examen_create
==================================================
*/

require_once APP_PATH . '/includes/header.php';
require_once APP_PATH . '/includes/sidebar.php';

$idDossier = (int)($idDossier ?? 0);
$typesExamens = $typesExamens ?? [];

$h = static function (mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Flash
function flash(string $key): string
{
    if (empty($_SESSION[$key])) return '';
    $msg = (string)$_SESSION[$key];
    unset($_SESSION[$key]);
    return $msg;
}
$flashSuccess = flash('flash_success');
$flashError   = flash('flash_error');
?>

<main>
    <h2>Demander un examen</h2>

    <?php if ($flashSuccess !== ''): ?>
        <p><?= $h($flashSuccess) ?></p>
    <?php endif; ?>

    <?php if ($flashError !== ''): ?>
        <p><?= $h($flashError) ?></p>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="index.php?action=examen_create">
            <input type="hidden" name="idDossier" value="<?= $idDossier ?>">

            <p>
                <label for="typeExamen"><b>Type examen *</b></label><br>
                <select id="typeExamen" name="typeExamen" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($typesExamens as $t): ?>
                        <option value="<?= $h($t) ?>"><?= $h($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="noteMedecin"><b>Note médecin (optionnel)</b></label><br>
                <textarea id="noteMedecin" name="noteMedecin" rows="4"></textarea>
            </p>

            <button class="btn btn-primary" type="submit">Envoyer la demande</button>
            <a class="btn" href="index.php?action=dossier_detail_medecin&id=<?= $idDossier ?>">Annuler</a>
        </form>
    </div>
</main>

<?php require_once APP_PATH . '/includes/footer.php'; ?>