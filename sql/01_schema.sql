-- Table HOPITAL
CREATE TABLE HOPITAL (
    idHopital TINYINT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(150) NOT NULL,
    ville VARCHAR(80) NOT NULL,
    region VARCHAR(80),
    capaciteTotalLit TINYINT UNSIGNED,
    capaciteTotalEquipement TINYINT UNSIGNED
);

-- Table SERVICE (✅ correction: parenthèses)
CREATE TABLE SERVICE (
    idService TINYINT AUTO_INCREMENT PRIMARY KEY,
    idHopital TINYINT NOT NULL,
    nom VARCHAR(80) NOT NULL,
    typeService VARCHAR(50) NOT NULL,
    capaciteLit TINYINT UNSIGNED,
    capaciteEquipement TINYINT UNSIGNED,
    FOREIGN KEY (idHopital) REFERENCES HOPITAL(idHopital) ON UPDATE CASCADE
);

-- Table PATIENT
CREATE TABLE PATIENT (
    idPatient TINYINT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(60) NOT NULL,
    prenom VARCHAR(60) NOT NULL,
    dateNaissance DATE NOT NULL,
    adresse VARCHAR(150),
    telephone VARCHAR(20),
    email VARCHAR(120),
    genre ENUM('Homme', 'Femme', 'Autre') NOT NULL,
    numeroCarteVitale VARCHAR(20),
    mutuelle VARCHAR(80),
    UNIQUE (email),
    UNIQUE (numeroCarteVitale)
);

-- Table DOSSIER_PATIENT
CREATE TABLE DOSSIER_PATIENT (
    idDossier TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPatient TINYINT NOT NULL,
    idHopital TINYINT NOT NULL,
    dateCreation DATE NOT NULL,
    dateAdmission DATE,
    dateSortie DATE,
    historiqueMedical TEXT,
    antecedant TEXT,
    etat_entree TEXT,
    diagnostic TEXT,
    examen TEXT,
    traitements TEXT,
    statut ENUM('ouvert', 'attente_consultation', 'consultation', 'attente_examen', 'attente_resultat', 'transfert', 'ferme') NOT NULL,
    niveau ENUM('1', '2', '3', '4', '5') NOT NULL,
    delaiPriseCharge ENUM('0', '10', '30', 'NonImmediat') NOT NULL,
    idTransfert TINYINT,
    FOREIGN KEY (idPatient) REFERENCES PATIENT(idPatient) ON UPDATE CASCADE,
    FOREIGN KEY (idHopital) REFERENCES HOPITAL(idHopital) ON UPDATE CASCADE,
    UNIQUE (idPatient)
);

-- Table PERSONNEL
CREATE TABLE PERSONNEL (
    idPersonnel TINYINT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(60) NOT NULL,
    prenom VARCHAR(60) NOT NULL,
    contact VARCHAR(120),
    dateEmbauche DATE,
    description TEXT,
    idService TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE
);

-- ✅ correction ordre de création :
-- MEDECIN / INFIRMIER / TECHNICIEN doivent être créées avant les tables qui les référencent (RESERVATION_* et MAINTENANCE_*)

-- Table MEDECIN
CREATE TABLE MEDECIN (
    idMedecin TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    spe VARCHAR(50) NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE
);

-- Table INFIRMIER
CREATE TABLE INFIRMIER (
    idInfirmier TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    spe VARCHAR(50) NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE
);

-- Table TECHNICIEN
CREATE TABLE TECHNICIEN (
    idTechnicien TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    niveauSupport VARCHAR(50) NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE
);

-- Table LIT
CREATE TABLE LIT (
    idLit TINYINT AUTO_INCREMENT PRIMARY KEY,
    numeroLit TINYINT NOT NULL,
    etatLit ENUM('disponible', 'reserve', 'occupe', 'en_panne', 'maintenance', 'HS') NOT NULL DEFAULT 'disponible',
    idService TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE,
    UNIQUE (idService, numeroLit)
);

-- Table EQUIPEMENT
CREATE TABLE EQUIPEMENT (
    idEquipement TINYINT AUTO_INCREMENT PRIMARY KEY,
    typeEquipement VARCHAR(60) NOT NULL,
    numeroEquipement TINYINT NOT NULL,
    etatEquipement ENUM('disponible', 'occupe', 'en_panne', 'maintenance', 'HS') NOT NULL DEFAULT 'disponible',
    localisation VARCHAR(80),
    idService TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE
);

-- Table CAPTEUR
CREATE TABLE CAPTEUR (
    idCapteur TINYINT AUTO_INCREMENT PRIMARY KEY,
    typeCapteur ENUM('pression', 'IOT', 'RFID') NOT NULL,
    etatCapteur ENUM('activer', 'desactiver', 'en_panne', 'maintenance') NOT NULL,
    idEquipement TINYINT NOT NULL,
    idLit TINYINT NOT NULL,
    FOREIGN KEY (idEquipement) REFERENCES EQUIPEMENT(idEquipement) ON UPDATE CASCADE,
    FOREIGN KEY (idLit) REFERENCES LIT(idLit) ON UPDATE CASCADE
);

-- Table ALERTE
CREATE TABLE ALERTE (
    idAlerte TINYINT AUTO_INCREMENT PRIMARY KEY,
    dateCreation DATE NOT NULL,
    typeAlerte ENUM('saturation', 'panne_Lit', 'panne_Equipement', 'Action') NOT NULL,
    action ENUM('attente_consultation', 'resultat_disponible', 'transfert_acceptte', 'lit_disponible', 'equipement_disponible'),
    statutAlerte ENUM('nonLu', 'Lu', 'Fait') NOT NULL DEFAULT 'nonLu',
    description TEXT
);

-- Table TRANSFERT_PATIENT
CREATE TABLE TRANSFERT_PATIENT (
    idTransfer TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPatient TINYINT NOT NULL,
    idHopital TINYINT NOT NULL,
    dateCreation DATETIME NOT NULL,
    statutTransfer ENUM('demande', 'attente_reponse', 'accepte', 'refuse', 'termine') NOT NULL DEFAULT 'demande',
    hopitalDestinataire VARCHAR(100) NOT NULL,
    serviceDestinataire VARCHAR(80),
    dateTransfer DATE,
    FOREIGN KEY (idPatient) REFERENCES PATIENT(idPatient) ON UPDATE CASCADE,
    FOREIGN KEY (idHopital) REFERENCES HOPITAL(idHopital) ON UPDATE CASCADE
);

-- Table MAINTENANCE_LIT
CREATE TABLE MAINTENANCE_LIT (
    idMaintenanceLit TINYINT AUTO_INCREMENT PRIMARY KEY,
    idLit TINYINT NOT NULL,
    idTechnicien TINYINT NOT NULL,
    dateDebutLit DATE NOT NULL,
    dateFinLit DATE,
    problemeLit TEXT NOT NULL,
    FOREIGN KEY (idLit) REFERENCES LIT(idLit) ON UPDATE CASCADE,
    FOREIGN KEY (idTechnicien) REFERENCES TECHNICIEN(idTechnicien) ON UPDATE CASCADE
);

-- Table MAINTENANCE_EQUIPEMENT
CREATE TABLE MAINTENANCE_EQUIPEMENT (
    idMaintenanceEquipement TINYINT AUTO_INCREMENT PRIMARY KEY,
    idEquipement TINYINT NOT NULL,
    idTechnicien TINYINT NOT NULL,
    dateDebutEquipement DATE NOT NULL,
    dateFinEquipement DATE,
    problemeEquipement TEXT NOT NULL,
    FOREIGN KEY (idEquipement) REFERENCES EQUIPEMENT(idEquipement) ON UPDATE CASCADE,
    FOREIGN KEY (idTechnicien) REFERENCES TECHNICIEN(idTechnicien) ON UPDATE CASCADE
);

-- Table RESERVATION_LIT
CREATE TABLE RESERVATION_LIT (
    idReservationLit TINYINT AUTO_INCREMENT PRIMARY KEY,
    idLit TINYINT NOT NULL,
    idInfirmier TINYINT NOT NULL,
    dateDebutReservation DATETIME NOT NULL,
    dateFinReservation DATETIME NOT NULL,
    CHECK (dateFinReservation > dateDebutReservation),
    FOREIGN KEY (idLit) REFERENCES LIT(idLit) ON UPDATE CASCADE,
    FOREIGN KEY (idInfirmier) REFERENCES INFIRMIER(idInfirmier) ON UPDATE CASCADE
);

-- Table RESERVATION_EQUIPEMENT
-- ✅ correction: idEquipement (colonne) + FK sur EQUIPEMENT(idEquipement)
CREATE TABLE RESERVATION_EQUIPEMENT (
    idReservationEquipment TINYINT AUTO_INCREMENT PRIMARY KEY,
    idEquipement TINYINT NOT NULL,
    idInfirmier TINYINT NOT NULL,
    idMedecin TINYINT NOT NULL,
    dateDebutReservation DATETIME NOT NULL,
    dateFinReservation DATETIME NOT NULL,
    CHECK (dateFinReservation > dateDebutReservation),
    FOREIGN KEY (idEquipement) REFERENCES EQUIPEMENT(idEquipement) ON UPDATE CASCADE,
    FOREIGN KEY (idInfirmier) REFERENCES INFIRMIER(idInfirmier) ON UPDATE CASCADE,
    FOREIGN KEY (idMedecin) REFERENCES MEDECIN(idMedecin) ON UPDATE CASCADE
);

-- Table DG
CREATE TABLE DG (
    idDg TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE
);

-- Table SOIGNE
CREATE TABLE SOIGNE (
    idSoigne TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    idDossier TINYINT NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE,
    FOREIGN KEY (idDossier) REFERENCES DOSSIER_PATIENT(idDossier) ON UPDATE CASCADE
);

-- Table GESTION_LIT
CREATE TABLE GESTION_LIT (
    idGestionLit TINYINT AUTO_INCREMENT PRIMARY KEY,
    idDossier TINYINT NOT NULL,
    idLit TINYINT NOT NULL,
    FOREIGN KEY (idDossier) REFERENCES DOSSIER_PATIENT(idDossier) ON UPDATE CASCADE,
    FOREIGN KEY (idLit) REFERENCES LIT(idLit) ON UPDATE CASCADE
);

-- Table GESTION_EQUIPEMENT
CREATE TABLE GESTION_EQUIPEMENT (
    idGestionEquipement TINYINT AUTO_INCREMENT PRIMARY KEY,
    idDossier TINYINT NOT NULL,
    idEquipement TINYINT NOT NULL,
    FOREIGN KEY (idDossier) REFERENCES DOSSIER_PATIENT(idDossier) ON UPDATE CASCADE,
    FOREIGN KEY (idEquipement) REFERENCES EQUIPEMENT(idEquipement) ON UPDATE CASCADE
);

-- Table DOSSIER_SERVICE
CREATE TABLE DOSSIER_SERVICE (
    idDossierService TINYINT AUTO_INCREMENT PRIMARY KEY,
    idService TINYINT NOT NULL,
    idDossier TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE,
    FOREIGN KEY (idDossier) REFERENCES DOSSIER_PATIENT(idDossier) ON UPDATE CASCADE
);

-- Table SERVICE_LIT
CREATE TABLE SERVICE_LIT (
    idServiceLit TINYINT AUTO_INCREMENT PRIMARY KEY,
    idService TINYINT NOT NULL,
    idLit TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE,
    FOREIGN KEY (idLit) REFERENCES LIT(idLit) ON UPDATE CASCADE
);

-- Table SERVICE_EQUIPEMENT
CREATE TABLE SERVICE_EQUIPEMENT (
    idServiceEquipement TINYINT AUTO_INCREMENT PRIMARY KEY,
    idService TINYINT NOT NULL,
    idEquipement TINYINT NOT NULL,
    FOREIGN KEY (idService) REFERENCES SERVICE(idService) ON UPDATE CASCADE,
    FOREIGN KEY (idEquipement) REFERENCES EQUIPEMENT(idEquipement) ON UPDATE CASCADE
);

-- Table GENERER_ALERTE_HOPITAL
CREATE TABLE GENERER_ALERTE_HOPITAL (
    idGenererAlerteHopital TINYINT AUTO_INCREMENT PRIMARY KEY,
    idHopital TINYINT NOT NULL,
    idAlerte TINYINT NOT NULL,
    FOREIGN KEY (idHopital) REFERENCES HOPITAL(idHopital) ON UPDATE CASCADE,
    FOREIGN KEY (idAlerte) REFERENCES ALERTE(idAlerte) ON UPDATE CASCADE
);

-- Table GENERER_ALERTE_PERSONNEL
CREATE TABLE GENERER_ALERTE_PERSONNEL (
    idGenererAlertePersonnel TINYINT AUTO_INCREMENT PRIMARY KEY,
    idPersonnel TINYINT NOT NULL,
    idAlerte TINYINT NOT NULL,
    FOREIGN KEY (idPersonnel) REFERENCES PERSONNEL(idPersonnel) ON UPDATE CASCADE,
    FOREIGN KEY (idAlerte) REFERENCES ALERTE(idAlerte) ON UPDATE CASCADE
);