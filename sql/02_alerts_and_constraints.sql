-- Migration : empêcher qu’un dossier réserve plus d’un lit
-- Objectif : chaque idDossier ne peut apparaître qu’une seule fois dans la table gestion_lit
-- Cela garantit qu’un dossier patient est associé à un seul lit à la fois

ALTER TABLE `gestion_lit`
ADD CONSTRAINT `uq_gestionlit_dossier` UNIQUE (`idDossier`);