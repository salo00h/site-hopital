<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/DossierModel.php';
require_once __DIR__ . '/../models/PatientModel.php';

function dossiers_list(): void
{
    $q = trim($_GET['q'] ?? '');
    $dossiers = getAllDossiers($q);

    require __DIR__ . '/../views/dossiers/liste.php';
}

function dossier_detail(): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo "ID dossier invalide";
        return;
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        http_response_code(404);
        echo "Dossier introuvable";
        return;
    }

    require __DIR__ . '/../views/dossiers/detail_infirmier.php';
}

function dossier_edit_form()
{
    if (!isset($_GET['id'])) {
        echo "ID dossier manquant";
        return;
    }

    $id = (int) $_GET['id'];
    if ($id <= 0) {
        echo "ID dossier invalide";
        return;
    }

    $dossier = getDossierById($id);
    if (!$dossier) {
        echo "Dossier introuvable";
        return;
    }

    $error = "";
    require __DIR__ . '/../views/dossiers/edit.php';
}

function dossier_update()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo "Accès interdit";
        return;
    }

    if (
        (!isset($_POST['idDossier'])) ||
        (!isset($_POST['idPatient'])) ||
        (!isset($_POST['nom'])) ||
        (!isset($_POST['prenom'])) ||
        (!isset($_POST['dateNaissance']))
    ) {
        echo "Formulaire incomplet";
        return;
    }

    $idDossier = (int) $_POST['idDossier'];
    $idPatient = (int) $_POST['idPatient'];

    if ($idDossier <= 0 || $idPatient <= 0) {
        echo "IDs invalides";
        return;
    }

    // ---------- Patient ----------
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $dateNaissance = trim($_POST['dateNaissance'] ?? '');

    $adresse = trim($_POST['adresse'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $genre = trim($_POST['genre'] ?? 'Homme');
    $numeroCarteVitale = trim($_POST['numeroCarteVitale'] ?? '');
    $mutuelle = trim($_POST['mutuelle'] ?? '');

    // (نخلي array ديال patient كما كان عندك)
    $patient = [
        'nom' => $nom,
        'prenom' => $prenom,
        'dateNaissance' => $dateNaissance,
        'adresse' => $adresse,
        'telephone' => $telephone,
        'email' => $email,
        'genre' => $genre,
        'numeroCarteVitale' => $numeroCarteVitale,
        'mutuelle' => $mutuelle,
    ];

    if ($nom === "" || $prenom === "" || $dateNaissance === "") {
        $dossier = getDossierById($idDossier);
        $error = "Nom / Prénom / Date naissance obligatoires.";
        require __DIR__ . '/../views/dossiers/edit.php';
        return;
    }

    // ---------- Dossier ----------
    $dateAdmission = isset($_POST['dateAdmission']) ? trim($_POST['dateAdmission']) : "";
    $dateSortie = isset($_POST['dateSortie']) ? trim($_POST['dateSortie']) : "";
    $historiqueMedical = isset($_POST['historiqueMedical']) ? trim($_POST['historiqueMedical']) : "";
    $antecedant = isset($_POST['antecedant']) ? trim($_POST['antecedant']) : "";
    $etat_entree = isset($_POST['etat_entree']) ? trim($_POST['etat_entree']) : "";
    $diagnostic = isset($_POST['diagnostic']) ? trim($_POST['diagnostic']) : "";
    $examen = isset($_POST['examen']) ? trim($_POST['examen']) : "";
    $traitements = isset($_POST['traitements']) ? trim($_POST['traitements']) : "";

    $statut = isset($_POST['statut']) ? trim($_POST['statut']) : "ouvert";
    $niveau = isset($_POST['niveau']) ? trim($_POST['niveau']) : "1";
    $delai = isset($_POST['delaiPriseCharge']) ? trim($_POST['delaiPriseCharge']) : "NonImmediat";

    // UPDATE
    updatePatient($idPatient, $nom, $prenom, $dateNaissance, $adresse, $telephone, $email, $genre, $numeroCarteVitale, $mutuelle);
    updateDossier($idDossier, $dateAdmission, $dateSortie, $historiqueMedical, $antecedant, $etat_entree, $diagnostic, $examen, $traitements, $statut, $niveau, $delai);

    header("Location: index.php?action=dossier_detail&id=" . $idDossier);
    exit;
}

function dossier_create_form(): void
{
    $error = '';
    require __DIR__ . '/../views/dossiers/create.php';
}

function dossier_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?action=dossier_create_form');
        exit;
    }

    // ===== Patient =====
    $patient = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'age' => (int)($_POST['age'] ?? 0), // نخليه كما هو
        'dateNaissance' => trim($_POST['dateNaissance'] ?? ''), // ✅ زيدناه (هو اللي ناقص)
        'adresse' => trim($_POST['adresse'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'genre' => trim($_POST['genre'] ?? 'Homme'),
        'numeroCarteVitale' => trim($_POST['numeroCarteVitale'] ?? ''),
        'mutuelle' => trim($_POST['mutuelle'] ?? ''),
        'etat_sante' => trim($_POST['etat_sante'] ?? ''), // نخليه كما هو
        // لو تحب تربطهم تلقائيًا بالمستخدم:
        'idHopital' => (int)($_POST['idHopital'] ?? 0),
        'idService' => (int)($_POST['idService'] ?? 0),
        'idTransfert' => (int)($_POST['idTransfert'] ?? 0),
    ];

    // Minimum validation
    if ($patient['nom'] === '' || $patient['prenom'] === '' || $patient['dateNaissance'] === '') {
        $error = "Nom, prénom et date de naissance sont obligatoires.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // ===== Dossier =====
    $dossier = [
        'idService' => (int)($_POST['idService'] ?? 0), // نخليه كما هو (حتى لو ما كيتستعملش دابا)
        'idHopital' => (int)($_POST['idHopital'] ?? 0), // ✅ مهم لـ createDossier()
        'dateAdmission' => trim($_POST['dateAdmission'] ?? date('Y-m-d')),
        'dateSortie' => null,
        'historiqueMedical' => trim($_POST['historiqueMedical'] ?? ''),
        'antecedant' => trim($_POST['antecedant'] ?? ''),
        'etat_entree' => trim($_POST['etat_entree'] ?? ''),
        'diagnostic' => trim($_POST['diagnostic'] ?? ''),
        'examen' => trim($_POST['examen'] ?? ''),
        'traitements' => trim($_POST['traitements'] ?? ''),
        'observations' => trim($_POST['observations'] ?? ''), // نخليه كما هو
        'statut' => trim($_POST['statut'] ?? 'ouvert'), // ✅ بدل OUVERT باش يطابق enum
        'niveau' => trim($_POST['niveau'] ?? '1'), // ✅ schema
        'delaiPriseCharge' => trim($_POST['delaiPriseCharge'] ?? 'NonImmediat'), // ✅ schema
        'idNiveauPriorite' => (int)($_POST['idNiveauPriorite'] ?? 0), // نخليه كما هو
        'idTransfert' => (int)($_POST['idTransfert'] ?? 0),
    ];

    if ($dossier['idHopital'] <= 0) {
        $error = "idHopital manquant.";
        require __DIR__ . '/../views/dossiers/create.php';
        return;
    }

    // Transaction: create patient ثم create dossier
    $newDossierId = createPatientAndDossier($patient, $dossier);

    header('Location: index.php?action=dossier_detail&id=' . $newDossierId);
    exit;
}