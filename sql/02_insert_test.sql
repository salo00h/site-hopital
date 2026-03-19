USE filrouge;

-- HOPITAUX
INSERT INTO hopital (nom, adresse, ville, region, capaciteTotalLit, capaciteTotalEquipement) VALUES
('Hopital Edouard Herriot', '5 Place d''Arsonval', 'Lyon', 'Auvergne-Rhone-Alpes', 80, 40),
('Hopital de la Croix-Rousse', '103 Grande Rue de la Croix-Rousse', 'Lyon', 'Auvergne-Rhone-Alpes', 70, 35),
('Hopital Lariboisiere', '2 Rue Ambroise Pare', 'Paris', 'Ile-de-France', 75, 38),
('Hopital Saint-Antoine', '184 Rue du Faubourg Saint-Antoine', 'Paris', 'Ile-de-France', 72, 36),
('CHU de Bordeaux', '1 Place Amelie Raba Leon', 'Bordeaux', 'Nouvelle-Aquitaine', 78, 39),
('Hopital de la Timone', '264 Rue Saint-Pierre', 'Marseille', 'Provence-Alpes-Cote d''Azur', 80, 40),
('CHU de Lille', '2 Avenue Oscar Lambret', 'Lille', 'Hauts-de-France', 65, 30),
('Hopital Purpan', '1 Place du Docteur Baylac', 'Toulouse', 'Occitanie', 70, 35),
('CHU de Nantes', '1 Place Alexis Ricordeau', 'Nantes', 'Pays de la Loire', 68, 32),
('Hopital Pellegrin', '2 Place Amelie Raba Leon', 'Bordeaux', 'Nouvelle-Aquitaine', 60, 28);

-- SERVICE
INSERT INTO service (idHopital, nom, typeService, capaciteLit, capaciteEquipement) VALUES
(1, 'Cardiologie', 'Medecine', 20, 10),
(1, 'Urgences', 'Urgence', 15, 12),
(2, 'Pediatrie', 'Medecine', 18, 8),
(2, 'Chirurgie Generale', 'Chirurgie', 16, 10),
(3, 'Neurologie', 'Medecine', 20, 9),
(4, 'Oncologie', 'Medecine', 18, 10),
(5, 'Reanimation', 'Soins Intensifs', 12, 14),
(6, 'Maternite', 'Obstetrique', 22, 7),
(7, 'Orthopedie', 'Chirurgie', 18, 10),
(8, 'Psychiatrie', 'Medecine', 16, 6);

-- PERSONNEL
INSERT INTO personnel (nom, prenom, contact, dateEmbauche, description, idService) VALUES
('Dubois', 'Pierre', 'pierre.dubois@hopital.fr', '2015-06-01', 'Cardiologue specialise en chirurgie interventionnelle.', 1),
('Lambert', 'Claire', 'claire.lambert@hopital.fr', '2018-09-15', 'Infirmiere en chef du service des urgences.', 2),
('Garnier', 'Paul', 'paul.garnier@hopital.fr', '2010-03-22', 'Pediatre specialise en neonatologie.', 3),
('Morel', 'Isabelle', 'isabelle.morel@hopital.fr', '2012-11-10', 'Chirurgienne generale, experte en laparoscopie.', 4),
('Fontaine', 'Julien', 'julien.fontaine@hopital.fr', '2019-01-07', 'Neurologue specialise dans le traitement des AVC.', 5),
('Chevalier', 'Marie', 'marie.chevalier@hopital.fr', '2016-04-18', 'Oncologue referente, coordinatrice des chimiotherapies.', 6),
('Marchand', 'Olivier', 'olivier.marchand@hopital.fr', '2008-07-30', 'Medecin reanimateur, responsable des soins intensifs.', 7),
('Boucher', 'Nathalie', 'nathalie.boucher@hopital.fr', '2020-02-14', 'Sage-femme cadre, responsable du suivi des grossesses.', 8),
('Robin', 'Eric', 'eric.robin@hopital.fr', '2013-08-25', 'Chirurgien orthopediste specialise en protheses.', 9),
('Blanc', 'Aurelie', 'aurelie.blanc@hopital.fr', '2017-05-03', 'Psychiatre referente, specialisee en therapies comportementales.', 10);

-- ROLES
INSERT INTO infirmier (idPersonnel, spe) VALUES
(2, 'Accueil'),
(8, 'Obstetrique');

INSERT INTO medecin (idPersonnel, spe) VALUES
(1, 'Cardiologie'),
(3, 'Pediatrie'),
(5, 'Neurologie'),
(6, 'Oncologie'),
(7, 'Reanimation');

INSERT INTO technicien (idPersonnel, niveauSupport) VALUES
(4, 'Niveau 2 - Chirurgie'),
(9, 'Niveau 2 - Orthopedie');

INSERT INTO dg (idPersonnel) VALUES
(10);

-- LITS
INSERT INTO lit (idService, numeroLit, etatLit) VALUES
(1, 101, 'disponible'),
(1, 102, 'occupe'),
(1, 103, 'disponible'),
(1, 104, 'maintenance'),
(1, 105, 'disponible'),
(2, 201, 'reserve'),
(2, 202, 'occupe'),
(2, 203, 'disponible'),
(2, 204, 'disponible'),
(2, 205, 'en_panne');

-- EQUIPEMENT
INSERT INTO equipement (idService, typeEquipement, numeroEquipement, etatEquipement, localisation) VALUES
(1, 'Electrocardiogramme', 1, 'disponible', 'Salle 1A - Cardiologie'),
(1, 'Defibrillateur', 2, 'occupe', 'Salle 1B - Cardiologie'),
(2, 'Respirateur', 1, 'disponible', 'Salle 2A - Urgences'),
(2, 'Moniteur multiparametre', 2, 'maintenance', 'Salle 2B - Urgences'),
(3, 'Incubateur', 1, 'disponible', 'Salle 3A - Pediatrie'),
(4, 'Table operatoire', 1, 'occupe', 'Bloc 4A - Chirurgie'),
(5, 'Ventilateur', 1, 'disponible', 'Salle 5A - Reanimation'),
(6, 'Table d''accouchement', 1, 'disponible', 'Salle 6A - Maternite'),
(7, 'Arthroscope', 1, 'en_panne', 'Bloc 7A - Orthopedie'),
(8, 'Fauteuil therapeutique', 1, 'disponible', 'Salle 8A - Psychiatrie');

-- CAPTEUR
INSERT INTO capteur (typeCapteur, etatCapteur, idEquipement, idLit) VALUES
('pression', 'activer', 1, 1),
('IOT', 'activer', 2, 2),
('RFID', 'desactiver', 3, 3),
('pression', 'maintenance', 4, 4),
('IOT', 'activer', 5, 5),
('RFID', 'activer', 6, 6),
('pression', 'activer', 7, 7),
('IOT', 'en_panne', 8, 8),
('RFID', 'activer', 9, 9),
('pression', 'desactiver', 10, 10);

-- ALERTE
INSERT INTO alerte (dateCreation, typeAlerte, action, statutAlerte, description) VALUES
('2024-01-10', 'panne_Lit', 'lit_disponible', 'Lu', 'Lit 101 signale en panne dans le service Cardiologie.'),
('2024-01-15', 'panne_Equipement', 'equipement_disponible', 'nonLu', 'Defibrillateur hors service en Cardiologie.'),
('2024-02-03', 'saturation', 'transfert_acceptte', 'Fait', 'Service Urgences sature, transfert initie.'),
('2024-02-20', 'Action', 'attente_consultation', 'nonLu', 'Demande de consultation neurologique en attente.'),
('2024-03-05', 'Action', 'resultat_disponible', 'Lu', 'Resultats d''examens oncologiques disponibles.'),
('2024-03-18', 'panne_Equipement', 'equipement_disponible', 'nonLu', 'Arthroscope en panne au service Orthopedie.'),
('2024-04-01', 'saturation', 'transfert_acceptte', 'Fait', 'Reanimation proche de la capacite maximale.'),
('2024-04-12', 'Action', 'lit_disponible', 'nonLu', 'Lit disponible en Maternite suite a une sortie.'),
('2024-05-07', 'panne_Lit', 'lit_disponible', 'Lu', 'Lit 701 signale en panne en Orthopedie.'),
('2024-05-20', 'Action', 'attente_consultation', 'nonLu', 'Consultation psychiatrique en attente de planification.');

-- MAINTENANCE_LIT
INSERT INTO maintenance_lit (idLit, idTechnicien, dateDebutLit, dateFinLit, problemeLit) VALUES
(6, 1, '2024-02-01', '2024-02-05', 'Matelas degrade, remplacement necessaire.'),
(9, 2, '2024-04-10', NULL, 'Mecanisme de reglage electrique defaillant.');

-- MAINTENANCE_EQUIPEMENT
INSERT INTO maintenance_equipement (idEquipement, idTechnicien, dateDebutEquipement, dateFinEquipement, problemeEquipement) VALUES
(4, 1, '2024-02-15', '2024-02-20', 'Moniteur multiparametre : ecran defaillant.'),
(9, 2, '2024-04-11', NULL, 'Arthroscope : panne optique, piece commandee.');

-- SERVICE_LIT
INSERT INTO service_lit (idService, idLit) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 4),
(3, 5),
(4, 6),
(5, 7),
(6, 8),
(7, 9),
(8, 10);

-- SERVICE_EQUIPEMENT
INSERT INTO service_equipement (idService, idEquipement) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 4),
(3, 5),
(4, 6),
(5, 7),
(6, 8),
(7, 9),
(8, 10);

-- GENERER_ALERTE_HOPITAL
INSERT INTO generer_alerte_hopital (idHopital, idAlerte) VALUES
(1, 1),
(1, 2),
(3, 3),
(3, 4),
(4, 5),
(7, 6),
(5, 7),
(6, 8),
(7, 9),
(8, 10);

-- GENERER_ALERTE_PERSONNEL
INSERT INTO generer_alerte_personnel (idPersonnel, idAlerte) VALUES
(4, 1),
(4, 2),
(5, 3),
(8, 4),
(9, 5),
(10, 6),
(7, 7),
(6, 8),
(3, 9),
(2, 10);

INSERT INTO users (idPersonnel, username, password_hash, role, is_active) VALUES
(2, 'accueil', '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'INFIRMIER_ACCUEIL', 1),
(8, 'infirmier', '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'INFIRMIER', 1),
(1, 'medecin', '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'MEDECIN', 1),
(4, 'technicien', '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'TECHNICIEN', 1);


-- TYPE_EXAMEN
INSERT INTO type_examen (libelle) VALUES
('Radiographie'),
('Scanner'),
('IRM'),
('Analyse de sang'),
('Echographie');