<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tables constants
|--------------------------------------------------------------------------
| هذا الملف يحتوي أسماء الجداول في قاعدة البيانات.
| الهدف: توحيد الأسماء وتجنب مشاكل الحروف الكبيرة والصغيرة
| خصوصًا بين Windows و Linux (Render / Railway).
|
| جميع الـ Models يجب أن تستعمل هذه الثوابت بدل كتابة
| أسماء الجداول مباشرة داخل SQL.
|--------------------------------------------------------------------------
*/

const T_PATIENT               = 'patient';
const T_USERS                 = 'users';

const T_DOSSIER               = 'dossier_patient';
const T_DOSSIER_SERVICE       = 'dossier_service';

const T_LIT                   = 'lit';
const T_GESTION_LIT           = 'gestion_lit';
const T_RESERVATION_LIT       = 'reservation_lit';
const T_SERVICE_LIT           = 'service_lit';

const T_EQUIPEMENT            = 'equipement';
const T_GESTION_EQUIPEMENT    = 'gestion_equipement';
const T_RESERVATION_EQUIPEMENT= 'reservation_equipement';
const T_SERVICE_EQUIPEMENT    = 'service_equipement';

const T_EXAMEN                = 'examen';

const T_PERSONNEL             = 'personnel';
const T_INFIRMIER             = 'infirmier';
const T_MEDECIN               = 'medecin';
const T_TECHNICIEN            = 'technicien';

const T_SERVICE               = 'service';
const T_HOPITAL               = 'hopital';

const T_ALERTE                = 'alerte';
const T_CAPTEUR               = 'capteur';

const T_TRANSFER_PATIENT      = 'transfert_patient';

const T_MAINTENANCE_EQUIPEMENT = 'maintenance_equipement';
const T_MAINTENANCE_LIT        = 'maintenance_lit';

const T_SOIGNE                = 'soigne';
