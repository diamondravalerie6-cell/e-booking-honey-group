-- ==========================================================
-- Migration : ajout de la connexion administrateur
-- À exécuter UNE SEULE FOIS sur une base e_booking existante
-- (phpMyAdmin > onglet Importer, ou onglet SQL)
-- ==========================================================

USE e_booking;

-- 1. Ajout du rôle sur les utilisateurs (client par défaut)
ALTER TABLE utilisateurs
    ADD COLUMN role ENUM('client', 'admin') NOT NULL DEFAULT 'client' AFTER mot_de_passe;

