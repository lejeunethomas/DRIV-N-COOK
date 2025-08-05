-- Suppression si existant
DROP TABLE IF EXISTS vente_details, demandes_camion, commande_details, commandes, ventes, camions, produits, users, clients, entrepots, stocks, menus, historique_reductions;

-- Table des clients (incluant admin)
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    points_fidelite INT DEFAULT 0,
    role ENUM('admin', 'client') DEFAULT 'client',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des utilisateurs (franchisés uniquement)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    lieu_installation VARCHAR(200),
    motivation TEXT,
    numero_permis VARCHAR(20) UNIQUE, 
    adresse TEXT,
    role ENUM('franchise') DEFAULT 'franchise',
    statut ENUM('en_attente', 'valide', 'refuse', 'desactive') DEFAULT 'en_attente',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des camions
CREATE TABLE camions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_camion VARCHAR(100),
    immatriculation VARCHAR(20) UNIQUE,
    etat VARCHAR(100) DEFAULT 'en service',
    date_entretien DATE,
    date_livraison DATE,
    emplacement VARCHAR(200),
    menu TEXT,
    jours VARCHAR(255),
    historique_entretiens TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des demandes de camion
CREATE TABLE demandes_camion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_camion VARCHAR(100) NOT NULL,
    numero_permis VARCHAR(20) NOT NULL, 
    emplacement VARCHAR(200) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    menu VARCHAR(500) NOT NULL,
    jours VARCHAR(200) NOT NULL,
    etat ENUM('en attente', 'validee', 'refusee') DEFAULT 'en attente',
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME,
    commentaire_admin TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des produits
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('aliment', 'boisson', 'préparé') DEFAULT 'aliment',
    prix_unitaire DECIMAL(10,2) NOT NULL,
    obligatoire BOOLEAN DEFAULT TRUE,
    quantite_minimale INT DEFAULT 0,
    reduction_fidelite DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Réduction en pourcentage pour les clients fidèles (0-100)'
);

-- Table des entrepôts
CREATE TABLE entrepots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    ville VARCHAR(100) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    pays VARCHAR(50) DEFAULT 'France',
    telephone VARCHAR(20),
    email VARCHAR(150),
    responsable VARCHAR(100),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8)
);

-- Table des stocks par entrepôt et produit
CREATE TABLE stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entrepot_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite DECIMAL(10,3) NOT NULL DEFAULT 0,
    unite ENUM('kg', 'litres', 'unites') NOT NULL DEFAULT 'kg',
    seuil_alerte DECIMAL(10,3) DEFAULT 10,
    derniere_maj DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entrepot_id) REFERENCES entrepots(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stock (entrepot_id, produit_id)
);

-- Table des commandes
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) DEFAULT 0,
    entrepot_id INT,
    statut ENUM('en_attente', 'validee', 'en_preparation', 'livree', 'annulee') DEFAULT 'en_attente',
    date_livraison_prevue DATE,
    commentaire_admin TEXT,
    validee_par INT,  -- Référence vers clients.id pour l'admin
    distance_km DECIMAL(8,2),
    temps_livraison_estime VARCHAR(50),
    urgence_livraison ENUM('normale', 'attention', 'urgente') DEFAULT 'normale',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (entrepot_id) REFERENCES entrepots(id),
    FOREIGN KEY (validee_par) REFERENCES clients(id)
);

-- Détail des produits commandés
CREATE TABLE commande_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table des ventes 
CREATE TABLE ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    camion_id INT,
    montant DECIMAL(10,2) NOT NULL,
    type_paiement ENUM('especes', 'carte', 'cheque', 'virement') DEFAULT 'especes',
    date_vente DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (camion_id) REFERENCES camions(id) ON DELETE SET NULL
);

-- Détails des ventes (produits vendus)
CREATE TABLE vente_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vente_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table des menus
CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    categorie ENUM('plat', 'boisson', 'dessert', 'accompagnement') DEFAULT 'plat',
    ingredients TEXT,
    allergenes VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ajouter une table de liaison entre les menus et les produits
CREATE TABLE menu_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite_necessaire DECIMAL(10,3) NOT NULL DEFAULT 1,
    unite ENUM('kg', 'litres', 'unites') DEFAULT 'unites',
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_menu_produit (menu_id, produit_id)
);

-- Données exemple pour l'admin
INSERT INTO clients (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Système', 'admin@drivncook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Mot de passe : password

-- Données exemple pour les produits
INSERT INTO produits (nom, type, prix_unitaire, obligatoire, quantite_minimale) VALUES
('Pain artisanal', 'aliment', 1.50, 1, 5),
('Steak haché bio', 'aliment', 3.80, 1, 2),
('Fromage cheddar', 'aliment', 2.20, 1, 3),
('Salade iceberg', 'aliment', 1.00, 1, 2),
('Tomates cerises', 'aliment', 2.50, 1, 1),
('Coca-Cola 33cl', 'boisson', 2.00, 1, 10),
('Eau minérale 50cl', 'boisson', 1.00, 1, 20),
('Jus d orange 25cl', 'boisson', 2.50, 1, 5),
('Burger complet', 'préparé', 8.90, 0, 0),
('Hot-dog artisanal', 'préparé', 6.50, 0, 0),
('Salade César', 'préparé', 7.80, 0, 0),
('Frites maison', 'préparé', 3.50, 0, 0);

-- Données exemple pour les entrepôts
INSERT INTO entrepots (nom, adresse, ville, code_postal, telephone, email, responsable, latitude, longitude) VALUES
('Entrepôt Central Paris', '15 Rue de la Logistique', 'Paris', '75015', '01 42 53 12 34', 'paris@drivncook.com', 'Jean Dupont', 48.8566, 2.3522),
('Entrepôt Lyon', '45 Avenue des Entreprises', 'Lyon', '69007', '04 78 92 15 67', 'lyon@drivncook.com', 'Marie Martin', 45.7640, 4.8357),
('Entrepôt Marseille', '23 Boulevard Industrial', 'Marseille', '13008', '04 91 45 78 90', 'marseille@drivncook.com', 'Pierre Durand', 43.2965, 5.3698);

-- Données exemple pour les stocks
INSERT INTO stocks (entrepot_id, produit_id, quantite, unite, seuil_alerte) VALUES
(1, 1, 500.0, 'kg', 50.0),    -- Pain artisanal à Paris
(1, 2, 200.0, 'kg', 20.0),    -- Steak haché à Paris
(1, 3, 150.0, 'kg', 15.0),    -- Fromage à Paris
(1, 6, 1000.0, 'unites', 100), -- Coca-Cola à Paris
(2, 1, 300.0, 'kg', 50.0),    -- Pain artisanal à Lyon
(2, 2, 150.0, 'kg', 20.0),    -- Steak haché à Lyon
(3, 1, 250.0, 'kg', 50.0),    -- Pain artisanal à Marseille
(3, 6, 800.0, 'unites', 100); -- Coca-Cola à Marseille

-- Données exemple pour les menus
INSERT INTO menus (user_id, nom, description, prix, categorie, ingredients, allergenes) VALUES
(1, 'Burger Classique', 'Pain artisanal, steak haché, salade, tomate, fromage cheddar', 8.90, 'plat', 'Pain, Steak haché, Salade, Tomate, Fromage cheddar', 'gluten, lactose'),
(1, 'Hot-Dog Gourmet', 'Saucisse artisanale, pain brioche, oignons confits', 6.50, 'plat', 'Saucisse, Pain brioche, Oignons', 'gluten'),
(1, 'Coca-Cola 33cl', 'Boisson gazeuse rafraîchissante', 2.50, 'boisson', 'Coca-Cola', ''),
(1, 'Frites maison', 'Pommes de terre fraîches, cuites dans notre huile spéciale', 3.50, 'accompagnement', 'Pommes de terre, Huile végétale', '');

-- Ajouter des données exemple pour la table de liaison menu_produits
INSERT INTO menu_produits (menu_id, produit_id, quantite_necessaire, unite) VALUES
-- Burger Classique (menu_id = 1)
(1, 1, 0.15, 'kg'),  -- Pain artisanal
(1, 2, 0.15, 'kg'),  -- Steak haché
(1, 5, 0.05, 'kg'),  -- Tomates
(1, 3, 0.03, 'kg'),  -- Fromage cheddar

-- Hot-Dog Gourmet (menu_id = 2)
(2, 1, 0.10, 'kg'),  -- Pain brioche
(2, 2, 0.12, 'kg'),  -- Saucisse

-- Coca-Cola (menu_id = 3) - produit direct
(3, 6, 1, 'unites'), -- Coca-Cola

-- Frites maison (menu_id = 4)
(4, 1, 0.20, 'kg');  -- Pommes de terre (supposons que c'est le produit 1)

-- Modifier les données exemple pour refléter la vraie logique métier
UPDATE produits SET 
    obligatoire = 0, 
    quantite_minimale = 0 
WHERE type IN ('aliment', 'préparé');

-- Les produits en partenariat commercial (obligatoires)
UPDATE produits SET 
    obligatoire = 1,
    quantite_minimale = 10
WHERE nom IN ('Coca-Cola 33cl', 'Eau minérale 50cl', 'Jus d orange 25cl');

-- Ajouter des produits partenaires exemple
INSERT INTO produits (nom, type, prix_unitaire, obligatoire, quantite_minimale) VALUES
('Yaourt Danone Vanille', 'préparé', 2.80, 1, 15),  -- Partenariat Danone
('Chips Lay s Nature', 'préparé', 2.20, 1, 20),    -- Partenariat Lay's
('Cookie Ben & Jerry s', 'préparé', 3.50, 1, 10),  -- Partenariat Ben & Jerry's
('Red Bull 25cl', 'boisson', 3.00, 1, 25);          -- Partenariat Red Bull

-- Mettre à jour les stocks avec les nouveaux produits partenaires
INSERT INTO stocks (entrepot_id, produit_id, quantite, unite, seuil_alerte) VALUES
-- Produits partenaires à Paris
(1, 13, 200.0, 'unites', 50),   -- Yaourt Danone
(1, 14, 300.0, 'unites', 80),   -- Chips Lay's
(1, 15, 150.0, 'unites', 30),   -- Cookie Ben & Jerry's
(1, 16, 400.0, 'unites', 100),  -- Red Bull

-- Produits partenaires à Lyon
(2, 13, 150.0, 'unites', 50),
(2, 14, 250.0, 'unites', 80),
(2, 16, 300.0, 'unites', 100),

-- Produits partenaires à Marseille  
(3, 13, 180.0, 'unites', 50),
(3, 15, 120.0, 'unites', 30),
(3, 16, 350.0, 'unites', 100);

-- Créer des vues pour faciliter les requêtes
CREATE VIEW view_stocks_alerte AS
SELECT 
    s.*,
    p.nom as produit_nom,
    e.nom as entrepot_nom,
    CASE 
        WHEN s.quantite = 0 THEN 1 
        WHEN s.quantite <= s.seuil_alerte THEN 1 
        ELSE 0 
    END as alerte
FROM stocks s
JOIN produits p ON s.produit_id = p.id
JOIN entrepots e ON s.entrepot_id = e.id;

-- Mettre à jour les produits partenaires existants avec des réductions exemple
UPDATE produits SET reduction_fidelite = 15.00 WHERE nom = 'Coca-Cola 33cl';
UPDATE produits SET reduction_fidelite = 10.00 WHERE nom = 'Eau minérale 50cl';
UPDATE produits SET reduction_fidelite = 20.00 WHERE nom = 'Yaourt Danone Vanille';
UPDATE produits SET reduction_fidelite = 12.00 WHERE nom = 'Chips Lay s Nature';
UPDATE produits SET reduction_fidelite = 25.00 WHERE nom = 'Cookie Ben & Jerry s';
UPDATE produits SET reduction_fidelite = 18.00 WHERE nom = 'Red Bull 25cl';