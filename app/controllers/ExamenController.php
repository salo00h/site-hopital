<?php
declare(strict_types=1);

/*
==================================================
  EXAMEN CONTROLLER (MVC)
==================================================
  Rôle :
  - Afficher le formulaire de demande d'examen (GET)
  - Traiter la création de la demande (POST)
  - Aucun SQL ici (on appelle ExamenModel)
==================================================
*/

require_once APP_PATH . '/includes/auth_guard.php';
require_once __DIR__ . '/../models/ExamenModel.php';

function abort_examen(int $status, string $message): void
{
    http_response_code($status);
    echo $message;
    exit;
}

function requirePostExamen(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        abort_examen(405, "Méthode non autorisée.");
    }
}

function examen_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);
    if ($idDossier <= 0) {
        abort_examen(400, "Paramètre idDossier invalide.");
    }

    $error = '';

    // Types d'examens depuis la base (table type_examen)
    $rows = examens_types_all();
    $typesExamens = array_map(
        static fn(array $r): string => (string)($r['libelle'] ?? ''),
        $rows
    );
    $typesExamens = array_values(array_unique(array_filter(
        $typesExamens,
        static fn(string $v): bool => $v !== ''
    )));

    if (empty($typesExamens)) {
        $error = "Aucun type d'examen n'est disponible (table type_examen vide).";
    }

    require APP_PATH . '/views/examens/form.php';
}

function examen_create_action(): void
{
    requireRole('MEDECIN');
    requirePostExamen();

    $idDossier   = (int)($_POST['idDossier'] ?? 0);
    $typeExamen  = trim((string)($_POST['typeExamen'] ?? ''));
    $noteMedecin = trim((string)($_POST['noteMedecin'] ?? ''));

    if ($idDossier <= 0 || $typeExamen === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=examen_form&idDossier=' . $idDossier);
        exit;
    }

    // Sécurité : vérifier que le type choisi existe bien (anti-manipulation POST)
    $rows = examens_types_all();
    $typesExamens = array_map(static fn($r) => (string)($r['libelle'] ?? ''), $rows);
    $typesExamens = array_values(array_unique(array_filter($typesExamens, static fn(string $v): bool => $v !== '')));

    if (!in_array($typeExamen, $typesExamens, true)) {
        $_SESSION['flash_error'] = "Type d'examen invalide.";
        header('Location: index.php?action=examen_form&idDossier=' . $idDossier);
        exit;
    }

    $note = ($noteMedecin === '') ? null : $noteMedecin;

    $ok = examen_create($idDossier, $typeExamen, $note);

    if ($ok) {
        $_SESSION['flash_success'] = "Examen demandé avec succès.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de la demande d'examen.";
    }

    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}