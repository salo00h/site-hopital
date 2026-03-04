USE filrouge;

-- HOPITAUX (données de test)

INSERT INTO hopital (nom, adresse, ville, region, capaciteTotalLit, capaciteTotalEquipement)
VALUES
('Hopital Central', '1 Rue de la Paix', 'Lyon', 'Auvergne-Rhone-Alpes', 50, 50),

('Hopital Saint Joseph', '12 Avenue Victor Hugo', 'Paris', 'Ile-de-France', 120, 80),

('Hopital Nord', '5 Boulevard des Alpes', 'Grenoble', 'Auvergne-Rhone-Alpes', 90, 60),

('Hopital Sud', '22 Rue Marseille', 'Marseille', 'Provence-Alpes-Cote d''Azur', 110, 70),

('Hopital Universitaire', '3 Rue Pasteur', 'Strasbourg', 'Grand Est', 150, 120),

('Hopital Regional', '45 Rue de Bordeaux', 'Bordeaux', 'Nouvelle-Aquitaine', 130, 90),

('Hopital Ouest', '18 Rue de Rennes', 'Rennes', 'Bretagne', 80, 55),

('Hopital Mediterranee', '7 Avenue Nice', 'Nice', 'Provence-Alpes-Cote d''Azur', 95, 65);

-- SERVICE
INSERT INTO service (idHopital, nom, typeService, capaciteLit, capaciteEquipement)
VALUES
(1, 'Accueil', 'Urgences', 10, 5),
(1, 'Medecine', 'Consultation', 20, 10),
(1, 'Technique', 'Maintenance', 5, 10);

-- PERSONNEL
INSERT INTO personnel (nom, prenom, contact, dateEmbauche, description, idService)
VALUES
('Saleh', 'Accueillant', 'saleh.accueil@filrouge.test', CURDATE(), 'Infirmier accueil', 1),
('Saleh', 'Medecin', 'saleh.medecin@filrouge.test', CURDATE(), 'Medecin generaliste', 2),
('Saleh', 'Tech', 'saleh.tech@filrouge.test', CURDATE(), 'Technicien support', 3);

-- ROLES (profil métier)
INSERT INTO infirmier (idPersonnel, spe) VALUES (1, 'Accueil');
INSERT INTO medecin (idPersonnel, spe) VALUES (2, 'Generaliste');
INSERT INTO technicien (idPersonnel, niveauSupport) VALUES (3, 'N1');

-- LITS (service Accueil = idService 1)
INSERT INTO lit (numeroLit, etatLit, idService) VALUES
(101, 'disponible', 1),
(102, 'disponible', 1),
(103, 'disponible', 1),
(104, 'disponible', 1),
(105, 'disponible', 1),
(106, 'disponible', 1),
(107, 'disponible', 1);


-- =========================================================
-- (C) Insertion des comptes utilisateurs (login)
-- =========================================================
-- Cette partie permet d’ajouter les comptes de connexion
-- pour le personnel médical dans l’application.
--
-- Important :
-- Le mot de passe n’est PAS stocké en texte clair.
-- Il est stocké sous forme de hash sécurisé généré par PHP.
--
-- Exemple de génération du hash en ligne de commande :
-- php -r "echo password_hash('root123', PASSWORD_DEFAULT);"
--
-- Cela garantit la sécurité des comptes utilisateurs.

INSERT INTO users (idPersonnel, username, password_hash, role, is_active) VALUES
(1, 'accueil',    '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'INFIRMIER_ACCUEIL', 1),
(2, 'medecin',    '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'MEDECIN', 1),
(3, 'technicien', '$2y$12$THUrwXqIvyh1PznXfyQNk.RPD/xIQt9DJhMnDBQ9xjpaHOEV.b0sm', 'TECHNICIEN', 1);



-- Types d'examens utilisés pour les tests

INSERT INTO type_examen (libelle) VALUES
('Radiographie'),
('Scanner'),
('IRM'),
('Analyse de sang'),
('Échographie');