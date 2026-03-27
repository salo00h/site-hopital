<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| EXAMEN CONTROLLER (MVC)
|--------------------------------------------------------------------------
| Rôle du contrôleur :
| - afficher le formulaire de demande d'examen ;
| - traiter la création d'une demande ;
| - gérer la réalisation et le résultat d'un examen ;
| - appeler uniquement les fonctions du modèle ;
| - ne contenir aucune requête SQL directe, sauf logique déjà présente
|   dans le code fourni et conservée telle quelle.
|
| Organisation du fichier :
| 1. Outils communs au module examen
| 2. Actions médecin
| 3. Actions infirmier
|--------------------------------------------------------------------------
*/

require_once APP_PATH . '/includes/auth_guard.php';
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/DossierModel.php';


/* ======================================================================
   OUTILS COMMUNS
   ====================================================================== */

/**
 * Arrêter immédiatement l'exécution avec un code HTTP.
 */
function abort_examen(int $status, string $message): void
{
    http_response_code($status);
    echo $message;
    exit;
}

/**
 * Vérifier que la requête HTTP est bien en POST.
 */
function requirePostExamen(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        abort_examen(405, "Méthode non autorisée.");
    }
}


/* ======================================================================
   ACTIONS MÉDECIN
   ====================================================================== */

/**
 * Afficher le formulaire de demande d'examen.
 */
function examen_form(): void
{
    requireRole('MEDECIN');

    $idDossier = (int)($_GET['idDossier'] ?? 0);

    if ($idDossier <= 0) {
        abort_examen(400, "Paramètre idDossier invalide.");
    }

    $error = '';

    /*
    |--------------------------------------------------------------------------
    | Récupération des types d'examens disponibles depuis la base
    |--------------------------------------------------------------------------
    */
    $rows = examens_types_all();

    $typesExamens = array_map(
        static fn(array $r): string => (string)($r['libelle'] ?? ''),
        $rows
    );

    /*
    |--------------------------------------------------------------------------
    | Nettoyage de la liste :
    | - suppression des valeurs vides ;
    | - suppression des doublons ;
    | - réindexation propre du tableau.
    |--------------------------------------------------------------------------
    */
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
 * Traiter la création d'une demande d'examen.
 */
function examen_create_action(): void
{
    requireRole('MEDECIN');
    requirePostExamen();

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres du formulaire
    |--------------------------------------------------------------------------
    */
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $typeExamen = trim((string)($_POST['typeExamen'] ?? ''));
    $noteMedecin = trim((string)($_POST['noteMedecin'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Vérification des champs obligatoires
    |--------------------------------------------------------------------------
    */
    if ($idDossier <= 0 || $typeExamen === '') {
        $_SESSION['flash_error'] = "Veuillez remplir les champs obligatoires.";
        header('Location: index.php?action=examen_form&idDossier=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Sécurité : vérifier que le type d'examen existe réellement en base
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | La note du médecin est facultative
    |--------------------------------------------------------------------------
    */
    $note = ($noteMedecin === '') ? null : $noteMedecin;

    require_once APP_PATH . '/models/LitModel.php';

    $idPersonnel = (int)($_SESSION['user']['idPersonnel'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | On conserve le médecin qui demande l'examen
    |--------------------------------------------------------------------------
    */
    $idMedecin = fetchIntOrNull(
        "SELECT idMedecin FROM MEDECIN WHERE idPersonnel = ? LIMIT 1",
        [$idPersonnel],
        'idMedecin'
    );

    /*
    |--------------------------------------------------------------------------
    | Appel du modèle pour créer l'examen
    |--------------------------------------------------------------------------
    */
    $ok = examen_create($idDossier, $typeExamen, $note, $idMedecin);

    if ($ok) {
        dossier_update_statut($idDossier, 'attente_examen');

        $_SESSION['flash_success'] = "Examen demandé avec succès. Le dossier passe en attente d'examen.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur lors de la demande d'examen.";
    }

    /*
    |--------------------------------------------------------------------------
    | Redirection vers le détail du dossier médecin
    |--------------------------------------------------------------------------
    */
    header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
    exit;
}


/* ======================================================================
   ACTIONS INFIRMIER
   ====================================================================== */

/**
 * Traiter la réalisation d'un examen par l'infirmier.
 *
 * Logique métier :
 * - l'examen passe à EN_COURS ;
 * - le dossier passe à attente_resultat.
 */
function examen_realiser_action(): void
{
    requireRole('INFIRMIER');
    requirePostExamen();

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres
    |--------------------------------------------------------------------------
    */
    $idExamen = (int)($_POST['idExamen'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Vérification de base
    |--------------------------------------------------------------------------
    */
    if ($idExamen <= 0 || $idDossier <= 0) {
        $_SESSION['flash_error'] = "Paramètres invalides.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier que l'examen existe
    |--------------------------------------------------------------------------
    */
    $examen = examen_get_by_id($idExamen);

    if (!$examen) {
        $_SESSION['flash_error'] = "Examen introuvable.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier la cohérence entre l'examen et le dossier
    |--------------------------------------------------------------------------
    */
    if ((int)($examen['idDossier'] ?? 0) !== $idDossier) {
        $_SESSION['flash_error'] = "Incohérence entre le dossier et l'examen.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier le statut actuel de l'examen
    |--------------------------------------------------------------------------
    */
    if (($examen['statut'] ?? '') !== 'EN_ATTENTE') {
        $_SESSION['flash_error'] = "Cet examen ne peut pas être réalisé.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | 1) L'examen passe à EN_COURS
    | 2) Le dossier passe à attente_resultat
    |--------------------------------------------------------------------------
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

/**
 * Saisir le résultat d'un examen côté infirmier.
 */
function examen_saisir_resultat_action(): void
{
    requireRole('INFIRMIER');
    requirePostExamen();

    /*
    |--------------------------------------------------------------------------
    | Lecture des paramètres
    |--------------------------------------------------------------------------
    */
    $idExamen = (int)($_POST['idExamen'] ?? 0);
    $idDossier = (int)($_POST['idDossier'] ?? 0);
    $resultat = trim((string)($_POST['resultat'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Vérification minimale des données
    |--------------------------------------------------------------------------
    */
    if ($idExamen <= 0 || $idDossier <= 0 || $resultat === '') {
        $_SESSION['flash_error'] = "Veuillez saisir un résultat valide.";
        header('Location: index.php?action=dossier_detail&id=' . $idDossier);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Enregistrement du résultat
    |--------------------------------------------------------------------------
    */
    $ok = examen_save_resultat($idExamen, $resultat);

    if ($ok) {
        /*
        ----------------------------------------------------------------------
        | Vérifier s'il reste encore des examens en attente sur ce dossier
        ----------------------------------------------------------------------
        */
        $examens = examens_get_by_dossier($idDossier);

        $resteEnAttente = false;
        foreach ($examens as $ex) {
            if (($ex['statut'] ?? '') === 'EN_ATTENTE') {
                $resteEnAttente = true;
                break;
            }
        }

        /*
        ----------------------------------------------------------------------
        | Si aucun examen ne reste en attente, on maintient le dossier
        | dans l'étape attente_resultat
        ----------------------------------------------------------------------
        */
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