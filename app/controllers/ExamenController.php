<?php
declare(strict_types=1);

/*
==================================================
  EXAMEN CONTROLLER (MVC)
==================================================
  Rôle :
  - Afficher le formulaire de demande d'examen
  - Traiter la création de la demande
  - Aucun SQL ici (appel du Model)
==================================================
*/

require_once APP_PATH . '/includes/auth_guard.php';
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/DossierModel.php';


/**
 * Arrêter l'exécution avec un code HTTP
 */
function abort_examen(int $status, string $message): void
{
    http_response_code($status);
    echo $message;
    exit;
}


/**
 * Vérifier que la requête est POST
 */
function requirePostExamen(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        abort_examen(405, "Méthode non autorisée.");
    }
}


/**
 * Afficher le formulaire de demande d'examen
 */
function examen_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    if ($idDossier <= 0) {
        abort_examen(400, "Paramètre idDossier invalide.");
    }

    $error = '';

    // Récupérer les types d'examens depuis la base
    $rows = examens_types_all();

    $typesExamens = array_map(
        static fn(array $r): string => (string)($r['libelle'] ?? ''),
        $rows
    );

    // Nettoyage de la liste
    $typesExamens = array_values(array_unique(array_filter(
        $typesExamens,
        static fn(string $v): bool => $v !== ''
    )));

    if (empty($typesExamens)) {
        $error = "Aucun type d'examen n'est disponible (table type_examen vide).";
    }

    require APP_PATH . '/views/examens/form.php';
}


/**
 * Traiter la création de la demande d'examen
 */
function examen_create_action(): void
{
    requireRole('MEDECIN');
    requirePostExamen();

    // Lecture des paramètres
    $idDossier   = (int)($_POST['idDossier'] ?? 0);
    $typeExamen  = trim((string)($_POST['typeExamen'] ?? ''));
    $noteMedecin = trim((string)($_POST['noteMedecin'] ?? ''));

    // Vérification des champs obligatoires
    if ($idDossier <= 0 || $typeExamen === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=examen_form&idDossier=' . $idDossier);
        exit;
    }

    // Sécurité : vérifier que le type existe dans la base
    $rows = examens_types_all();

    $typesExamens = array_map(
        static fn($r) => (string)($r['libelle'] ?? ''),
        $rows
    );

    $typesExamens = array_values(array_unique(array_filter(
        $typesExamens,
        static fn(string $v): bool => $v !== ''
    )));

    if (!in_array($typeExamen, $typesExamens, true)) {
        $_SESSION['flash_error'] = "Type d'examen invalide.";
        header('Location: index.php?action=examen_form&idDossier=' . $idDossier);
        exit;
    }

    // Note facultative
    $note = ($noteMedecin === '') ? null : $noteMedecin;

    // Appel du Model
    $ok = examen_create($idDossier, $typeExamen, $note);

    if ($ok) {
     dossier_update_statut($idDossier, 'attente_examen');
     $_SESSION['flash_success'] = "Examen demandé avec succès. Le dossier passe en attente d'examen.";
     $_SESSION['flash_error']   = "";
    } else {
     $_SESSION['flash_success'] = "";
     $_SESSION['flash_error']   = "Erreur lors de la demande d'examen.";
    }

    // Redirection vers le dossier
    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}

/**
 * Traiter la réalisation d'un examen par l'infirmière.
 * - Le statut de l'examen passe à TERMINE
 * - Le statut du dossier passe à attente_resultat
 */
function examen_realiser_action(): void
{
    requireRole('INFIRMIER');
    requirePostExamen();

    // Lecture des paramètres
    $idExamen  = (int)($_POST['idExamen'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);

    // Vérification de base
    if ($idExamen <= 0 || $idDossier <= 0) {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Vérifier que l'examen existe
    $examen = examen_get_by_id($idExamen);

    if (!$examen) {
        $_SESSION['flash_error'] = "Examen introuvable.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Vérifier que l'examen appartient bien au dossier
    if ((int)($examen['idDossier'] ?? 0) !== $idDossier) {
        $_SESSION['flash_error'] = "Incohérence entre le dossier et l'examen.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    // Vérifier le statut actuel de l'examen
    if (($examen['statut'] ?? '') !== 'EN_ATTENTE') {
        $_SESSION['flash_error'] = "Cet examen ne peut pas être réalisé.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
      1) L'examen passe à TERMINE
      2) Le dossier passe à attente_resultat
    */
    $ok = examen_update_statut($idExamen, 'EN_COURS');

    if ($ok) {
        dossier_update_statut($idDossier, 'attente_resultat');

        $_SESSION['flash_success'] = "Examen en cours. Veuillez saisir le résultat pour terminer l'examen.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de la réalisation de l'examen.";
    }

    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}


function examen_saisir_resultat_action(): void
{
    requireRole('INFIRMIER');
    requirePostExamen();

    $idExamen  = (int)($_POST['idExamen'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $resultat  = trim((string)($_POST['resultat'] ?? ''));

    if ($idExamen <= 0 || $idDossier <= 0 || $resultat === '') {
        $_SESSION['flash_error'] = "Veuillez saisir un résultat valide.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    $ok = examen_save_resultat($idExamen, $resultat);

    if ($ok) {
        // vérifier s'il reste examens en attente
        $examens = examens_get_by_dossier($idDossier);

        $resteEnAttente = false;
        foreach ($examens as $ex) {
            if (($ex['statut'] ?? '') === 'EN_ATTENTE') {
                $resteEnAttente = true;
                break;
            }
        }

        if (!$resteEnAttente) {
            dossier_update_statut($idDossier, 'attente_resultat');
        }

        $_SESSION['flash_success'] = "Résultat enregistré avec succès.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de l'enregistrement.";
    }

    header('Location: index.php?action=dossier_detail&id=' . $idDossier);
    exit;
}

