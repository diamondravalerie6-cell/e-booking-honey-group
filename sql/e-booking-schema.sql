-- ==========================================================
-- Plateforme e-Booking Hôtels & Location de Voitures
-- Script de création de la base de données (MLD -> SQL)
-- Basé sur le MCD validé par Honey Group
-- À importer via phpMyAdmin, ou : mysql -u root -p < e-booking-schema.sql
-- ==========================================================

CREATE DATABASE IF NOT EXISTS e_booking
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE e_booking;

-- --------------------------------------------------------
-- Table : utilisateurs (Touristes / Voyageurs)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe   VARCHAR(255) NOT NULL COMMENT 'Mot de passe haché (jamais en clair)',
    role           ENUM('client', 'admin') NOT NULL DEFAULT 'client',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : partenaires (Hôteliers / Loueurs de voitures)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS partenaires (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nom_entreprise   VARCHAR(100) NOT NULL,
    type_activite    ENUM('hotel', 'location_voiture', 'mixte') NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    taux_commission  DECIMAL(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Taux en pourcentage, ex: 10.00 = 10%',
    statut           ENUM('en_attente', 'valide', 'refuse') NOT NULL DEFAULT 'en_attente' COMMENT 'Validation par l administrateur',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : hotels
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS hotels (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_partenaire       INT NOT NULL,
    nom_etablissement   VARCHAR(100) NOT NULL,
    ville               VARCHAR(100) NOT NULL,
    tarif_nuit_moyen    DECIMAL(10,2) NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hotel_partenaire FOREIGN KEY (id_partenaire)
        REFERENCES partenaires(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : vehicules
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicules (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_partenaire       INT NOT NULL,
    modele              VARCHAR(100) NOT NULL,
    categorie_vehicule  VARCHAR(50) NOT NULL COMMENT 'Ex: 4x4, citadine, SUV',
    tarif_journalier    DECIMAL(10,2) NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicule_partenaire FOREIGN KEY (id_partenaire)
        REFERENCES partenaires(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : reservations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur      INT NOT NULL,
    date_reservation    DATE NOT NULL,
    montant_total       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    montant_commission  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut              ENUM('en_attente', 'confirmee', 'annulee', 'terminee') NOT NULL DEFAULT 'en_attente',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservation_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : lignes_reservation
-- Une réservation peut contenir plusieurs prestations
-- (un ou plusieurs hôtels et/ou véhicules)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS lignes_reservation (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation    INT NOT NULL,
    type_prestation   ENUM('hotel', 'vehicule') NOT NULL,
    id_ressource      INT NOT NULL COMMENT 'Référence vers hotels.id ou vehicules.id selon type_prestation',
    date_debut        DATE NOT NULL,
    date_fin          DATE NOT NULL,
    sous_total        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_ligne_reservation FOREIGN KEY (id_reservation)
        REFERENCES reservations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (date_fin >= date_debut)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table : commissions_paiements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS commissions_paiements (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation    INT NOT NULL,
    id_partenaire     INT NOT NULL,
    montant_du        DECIMAL(10,2) NOT NULL,
    statut_paiement   ENUM('du', 'verse') NOT NULL DEFAULT 'du',
    date_versement    DATE NULL,
    CONSTRAINT fk_commission_reservation FOREIGN KEY (id_reservation)
        REFERENCES reservations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_commission_partenaire FOREIGN KEY (id_partenaire)
        REFERENCES partenaires(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Index de performance sur les clés étrangères fréquemment
-- utilisées dans les recherches et jointures
-- --------------------------------------------------------
CREATE INDEX idx_hotels_ville ON hotels(ville);
CREATE INDEX idx_vehicules_categorie ON vehicules(categorie_vehicule);
CREATE INDEX idx_reservations_statut ON reservations(statut);
CREATE INDEX idx_lignes_dates ON lignes_reservation(date_debut, date_fin);
CREATE INDEX idx_commissions_statut ON commissions_paiements(statut_paiement);

-- --------------------------------------------------------
