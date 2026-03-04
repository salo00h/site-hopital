<?php
declare(strict_types=1);

/*
==================================================
 CONTROLLER : EquipementController
==================================================
 Rôle :
 - Gérer les actions liées aux équipements
 - Appeler le Model
 - Charger les vues
==================================================
*/

require_once APP_PATH . '/models/EquipementModel.php';


/**
 * Affiche la liste des équipements.
 */
function equipements_list_medecin(): void
{
    $equipements = equipements_get_all();

    require APP_PATH . '/views/equipements/liste_medecin.php';
}


/**
 * Affiche le formulaire de réservation.
 * On garde aussi l'idDossier pour savoir à quel patient
 * on réserve l’équipement.
 */
function equipement_reserver_form(): void
{
    $idEquipement = (int)($_GET['idEquipement'] ?? 0);
    $idDossier    = (int)($_GET['idDossier'] ?? 0);

    if ($idEquipement <= 0) {
        http_response_code(400);
        exit('ID équipement invalide.');
    }

    if ($idDossier <= 0) {
        http_response_code(400);
        exit('ID dossier invalide.');
    }

    // Récupération de l’équipement (pour affichage / vérification)
    $equipement = equipement_get_by_id($idEquipement);

    if (!$equipement) {
        http_response_code(404);
        exit('Équipement introuvable.');
    }

    require APP_PATH . '/views/equipements/reserver_form.php';
}


/**
 * Traite la réservation d’un équipement pour un dossier.
 *
 * Règles métier :
 * - On réserve uniquement si l’équipement est "disponible"
 * - On enregistre la relation dossier ↔ équipement dans GESTION_EQUIPEMENT (traçabilité)
 * - Puis on met à jour l’état de l’équipement à "occupe"
 *
 * On utilise une transaction pour garantir que les 2 opérations
 * (INSERT + UPDATE) se font ensemble, ou pas du tout.
 */
function equipement_reserver(): void
{
    // Sécurité : cette action doit être appelée en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Méthode non autorisée.');
    }

    // Lecture et validation des paramètres envoyés par le formulaire
    $idEquipement = (int)($_POST['idEquipement'] ?? 0);
    $idDossier    = (int)($_POST['idDossier'] ?? 0);

    if ($idEquipement <= 0 || $idDossier <= 0) {
        http_response_code(400);
        exit('Paramètres invalides.');
    }

    // Vérifier que l’équipement existe
    $equipement = equipement_get_by_id($idEquipement);
    if (!$equipement) {
        http_response_code(404);
        exit('Équipement introuvable.');
    }

    // Transaction : insertion du lien + mise à jour de l’état (une seule opération logique)
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Enregistrer la liaison dossier ↔ équipement (traçabilité)
        // ✅ Le Model renvoie false si l'équipement est en panne ou non disponible
        $ok = gestion_equipement_add($idDossier, $idEquipement);

        if (!$ok) {
            // On annule la transaction (rien n'a été confirmé)
            $pdo->rollBack();

            // ✅ Message d'erreur si l'équipement est en panne ou non disponible
            $_SESSION['flash_error'] = "Impossible : équipement en panne ou non disponible.";

            header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
            exit;
        }

        // 2) Mettre l’équipement en état "occupe"
        equipement_set_occupe($idEquipement);

        // Valider les changements
        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        // Annuler si une des étapes échoue
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible.";
        header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
        exit;
    }
}


/**
 * Signaler une panne sur un équipement.
 * Accepte idEquipement ou id dans l'URL.
 */
function equipement_signaler_panne(): void
{
    // Vérifie que l'utilisateur est médecin
    requireRole('MEDECIN');

    // Récupère l'idEquipement depuis l'URL
    // Compatible avec ?idEquipement= ou ?id=
    $idEquipement = (int)($_GET['idEquipement'] ?? ($_GET['id'] ?? 0));

    if ($idEquipement <= 0) {
        http_response_code(400);
        echo "Paramètre idEquipement invalide.";
        exit;
    }

    // Met l'équipement en panne
    $ok = equipement_set_panne($idEquipement);

    if ($ok) {
        $_SESSION['flash_success'] = "Panne signalée. L'équipement est maintenant en panne.";
        $_SESSION['flash_error'] = "";
    } else {
        $_SESSION['flash_success'] = "";
        $_SESSION['flash_error'] = "Erreur : impossible de signaler la panne.";
    }

    // Retour vers la liste des équipements
    $idDossier = (int)($_GET['idDossier'] ?? 0);

    $url = "index.php?action=equipements_list_medecin";
    if ($idDossier > 0) {
        $url .= "&idDossier=" . $idDossier;
    }

    header("Location: $url");
    exit;
}