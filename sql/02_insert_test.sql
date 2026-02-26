USE filrouge;

-- HOPITAL
INSERT INTO hopital (nom, adresse, ville, region, capaciteTotalLit, capaciteTotalEquipement)
VALUES ('Hopital Central', '1 Rue de la Paix', 'Lyon', 'Auvergne-Rhone-Alpes', 50, 50);

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