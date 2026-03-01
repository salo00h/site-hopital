<?php
declare(strict_types=1);

// ===============================
// TRANSFERT MODEL
// ===============================
// Rôle : gérer les demandes de transfert (INSERT / SELECT simple)
// Style : simple (prof), PDO + requêtes préparées

require_once APP_PATH . '/config/database.php';

/**
 * تعديل سهل إذا أسماء الجدول/الأعمدة تختلف عندك
 */
const TRANSFERT_TABLE = 'TRANSFERT';

// أسماء الأعمدة كما عندك (عدّلها فقط إذا تختلف)
const COL_ID_DOSSIER     = 'idDossier';
const COL_HOPITAL_CIBLE  = 'hopitalCible';
const COL_MOTIF          = 'motif';
const COL_DATE_DEMANDE   = 'dateDemande';
const COL_STATUT         = 'statut';

// قيم الحالة (عدّلها إذا عندك ENUM مختلف)
const STATUT_EN_ATTENTE_VALIDATION = 'EN_ATTENTE_VALIDATION';

/**
 * إنشاء طلب تحويل (Demande de transfert)
 */
function transfert_create(int $idDossier, string $hopitalCible, string $motif): bool
{
    $pdo = db();

    $sql = "
        INSERT INTO " . TRANSFERT_TABLE . " 
        (" . COL_ID_DOSSIER . ", " . COL_HOPITAL_CIBLE . ", " . COL_MOTIF . ", " . COL_DATE_DEMANDE . ", " . COL_STATUT . ")
        VALUES
        (:idDossier, :hopitalCible, :motif, NOW(), :statut)
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':idDossier'    => $idDossier,
        ':hopitalCible' => $hopitalCible,
        ':motif'        => $motif,
        ':statut'       => STATUT_EN_ATTENTE_VALIDATION,
    ]);
}

/**
 * (اختياري) جلب آخر طلب تحويل مرتبط بدوسيي معيّن
 * مفيد إذا تريد تعرض الطلب للطبيب/الممرض داخل detail.
 */
function transfert_find_by_dossier(int $idDossier): ?array
{
    $pdo = db();

    $sql = "
        SELECT *
        FROM " . TRANSFERT_TABLE . "
        WHERE " . COL_ID_DOSSIER . " = :idDossier
        ORDER BY " . COL_DATE_DEMANDE . " DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':idDossier' => $idDossier]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}