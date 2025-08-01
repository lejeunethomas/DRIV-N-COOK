-- Suppression si existant
DROP TABLE IF EXISTS demandes_camion, commande_details, commandes, ventes, camions, produits, users, clients;

-- Table des utilisateurs
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
    role ENUM('admin', 'franchise') DEFAULT 'franchise',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des camions
CREATE TABLE camions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_camion VARCHAR(100),
    immatriculation VARCHAR(20) UNIQUE,
    état VARCHAR(100) DEFAULT 'en service',
    date_entretien DATE,
    localisation VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des demandes de camion
CREATE TABLE demandes_camion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom_camion VARCHAR(100) NOT NULL,
    numero_permis VARCHAR(20) NOT NULL, 
    emplacement VARCHAR(200) NOT NULL,
    menu VARCHAR(500) NOT NULL,
    jours VARCHAR(200) NOT NULL,
    etat ENUM('en attente', 'validee', 'refusee') DEFAULT 'en attente',
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (numero_permis) REFERENCES users(numero_permis) ON UPDATE CASCADE
);

-- Table des produits
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('aliment', 'boisson', 'préparé') DEFAULT 'aliment',
    prix_unitaire DECIMAL(10,2) NOT NULL,
    obligatoire BOOLEAN DEFAULT TRUE,
    entrepot_id INT
);

-- Table des commandes
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Détail des produits commandés
CREATE TABLE commande_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table des ventes 
CREATE TABLE ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date_vente DATE NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL,
    nb_clients INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des clients
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    points_fidelite INT DEFAULT 0,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);
