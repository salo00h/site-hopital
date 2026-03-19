-- ============================================================
-- MISES À JOUR ET CONTRAINTES AJOUTÉES PENDANT LE DÉVELOPPEMENT
-- ============================================================


-- ------------------------------------------------------------
-- 1) Empêcher qu’un dossier réserve plus d’un seul lit
-- Chaque dossier patient ne peut être lié qu’à un seul lit
-- à la fois dans la table gestion_lit.
-- Cela évite d’avoir deux lits réservés pour le même dossier.
-- ------------------------------------------------------------
ALTER TABLE `gestion_lit`
ADD CONSTRAINT `uq_gestionlit_dossier` UNIQUE (`idDossier`);


-- ------------------------------------------------------------
-- 2) Amélioration de la table examen
-- Au début du projet, la table examen contenait seulement
-- les informations de base :
-- - la demande d’examen
-- - la note du médecin
-- - la date de demande
-- - le statut
--
-- Ensuite, nous avons ajouté :
-- - resultat : pour enregistrer le résultat de l’examen
-- - dateResultat : pour enregistrer la date et l’heure
--   où le résultat a été saisi
--
-- Cela permet de rendre le flux plus logique dans l’application.
-- ------------------------------------------------------------
ALTER TABLE `examen`
ADD COLUMN `resultat` TEXT NULL AFTER `noteMedecin`,
ADD COLUMN `dateResultat` DATETIME NULL AFTER `dateDemande`;


-- ------------------------------------------------------------
-- 3) Contrôle logique sur les statuts
-- Si un examen est marqué comme TERMINE,
-- alors un résultat doit obligatoirement exister.
--
-- Exemple :
-- - TERMINE + resultat rempli  -> autorisé
-- - TERMINE + resultat vide    -> refusé
-- ------------------------------------------------------------
ALTER TABLE `examen`
ADD CONSTRAINT `chk_examen_termine_resultat`
CHECK (
    `statut` <> 'TERMINE'
    OR (`resultat` IS NOT NULL AND TRIM(`resultat`) <> '')
);


-- ------------------------------------------------------------
-- 4) Contrôle logique sur la date du résultat
-- Si un résultat existe, alors la date du résultat doit aussi
-- être enregistrée.
--
-- Exemple :
-- - resultat vide                     -> autorisé
-- - resultat rempli + dateResultat    -> autorisé
-- - resultat rempli + dateResultat NULL -> refusé
-- ------------------------------------------------------------
ALTER TABLE `examen`
ADD CONSTRAINT `chk_examen_resultat_date`
CHECK (
    `resultat` IS NULL
    OR `dateResultat` IS NOT NULL
);



-- Ajout d’un nouveau type d’alerte "demande_transfert"
-- afin de permettre au médecin d’envoyer une demande de transfert au directeur
-- sous forme de notification uniquement (sans créer un transfert direct).
-- Cette modification est réalisée dans le fichier d’updates
-- sans modifier le schéma initial de la base de données.
ALTER TABLE alerte
MODIFY COLUMN typeAlerte
ENUM('saturation', 'panne_Lit', 'panne_Equipement', 'Action', 'demande_transfert')
NOT NULL;

