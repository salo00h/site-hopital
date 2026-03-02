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
 */
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

    // Bloquer la réservation si l’équipement n’est pas disponible
    if (($equipement['etatEquipement'] ?? '') !== 'disponible') {
        $_SESSION['flash_error'] = "Cet équipement n'est pas disponible. Impossible de le réserver.";
        header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
        exit;
    }

    // Transaction : insertion du lien + mise à jour de l’état (une seule opération logique)
    $pdo = db();
    $pdo->beginTransaction();

    try {
        // 1) Enregistrer la liaison dossier ↔ équipement (traçabilité)
        gestion_equipement_add($idDossier, $idEquipement);

        // 2) Mettre l’équipement en état "occupe"
        equipement_set_occupe($idEquipement);

        // Valider les changements
        $pdo->commit();

        $_SESSION['flash_success'] = "Équipement réservé avec succès.";
        header('Location: index.php?action=dossier_detail_medecin&id=' . $idDossier);
        exit;

    } catch (Throwable $e) {
        // Annuler si une des deux étapes échoue
        $pdo->rollBack();

        $_SESSION['flash_error'] = "Erreur : réservation impossible.";
        header('Location: index.php?action=equipements_list_medecin&idDossier=' . $idDossier);
        exit;
    }
}