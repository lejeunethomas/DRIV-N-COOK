-- Suppression de la base de données si elle existe et recréation
DROP DATABASE IF EXISTS `drivncook`;
CREATE DATABASE `drivncook` CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `drivncook`;

-- Désactiver les vérifications de clés étrangères pour éviter les erreurs lors de la suppression
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer toutes les tables si elles existent
DROP TABLE IF EXISTS `vente_details`;
DROP TABLE IF EXISTS `ventes`;
DROP TABLE IF EXISTS `menu_produits`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `stocks`;
DROP TABLE IF EXISTS `commande_details`;
DROP TABLE IF EXISTS `commandes`;
DROP TABLE IF EXISTS `demandes_camion`;
DROP TABLE IF EXISTS `camions`;
DROP TABLE IF EXISTS `entrepots`;
DROP TABLE IF EXISTS `produits`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `clients`;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : mar. 05 août 2025 à 19:13
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `drivncook`
--

-- --------------------------------------------------------

--
-- Structure de la table `camions`
--

CREATE TABLE `camions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom_camion` varchar(100) DEFAULT NULL,
  `immatriculation` varchar(20) DEFAULT NULL,
  `etat` varchar(100) DEFAULT 'en service',
  `date_entretien` date DEFAULT NULL,
  `date_livraison` date DEFAULT NULL,
  `emplacement` varchar(200) DEFAULT NULL,
  `menu` text,
  `jours` varchar(255) DEFAULT NULL,
  `historique_entretiens` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `camions`
--

INSERT INTO `camions` (`id`, `user_id`, `nom_camion`, `immatriculation`, `etat`, `date_entretien`, `date_livraison`, `emplacement`, `menu`, `jours`, `historique_entretiens`) VALUES
(1, 1, 'CACA braiser', 'DY-506-FU', 'en_service', NULL, '2025-08-19', 'bernay', 'pouller braiser', 'Lundi,Mercredi,Jeudi,Vendredi', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mot_de_passe` varchar(255) NOT NULL,
  `points_fidelite` int(11) DEFAULT '0',
  `role` enum('admin','client') DEFAULT 'client',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `prenom`, `email`, `telephone`, `created_at`, `mot_de_passe`, `points_fidelite`, `role`, `date_inscription`) VALUES
(1, 'Admin', 'Système', 'admin@drivncook.com', NULL, '2025-08-05 17:43:47', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'admin', '2025-08-05 18:01:12'),
(2, 'LEJEUNE', 'thomas', 'thomas2004.lejeune@gmail.com', '', '2025-08-05 17:43:47', '$2y$10$.xlGtNIjiheNJihJT9kkxe84Ss8rI5I7qRVFv7/uubCRFjOEP8tAC', 0, 'client', '2025-08-05 19:22:47');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) DEFAULT '0.00',
  `entrepot_id` int(11) DEFAULT NULL,
  `statut` enum('en_attente','validee','en_preparation','livree','annulee') DEFAULT 'en_attente',
  `date_livraison_prevue` date DEFAULT NULL,
  `commentaire_admin` text,
  `validee_par` int(11) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `temps_livraison_estime` varchar(50) DEFAULT NULL,
  `urgence_livraison` enum('normale','attention','urgente') DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `user_id`, `date_commande`, `total`, `entrepot_id`, `statut`, `date_livraison_prevue`, `commentaire_admin`, `validee_par`, `distance_km`, `temps_livraison_estime`, `urgence_livraison`) VALUES
(1, 1, '2025-08-05 20:36:41', '54.90', 1, 'en_attente', NULL, NULL, NULL, NULL, NULL, 'normale');

-- --------------------------------------------------------

--
-- Structure de la table `commande_details`
--

CREATE TABLE `commande_details` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `commande_details`
--

INSERT INTO `commande_details` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_total`, `prix_unitaire`) VALUES
(1, 1, 3, 5, '11.00', '2.20'),
(2, 1, 1, 7, '10.50', '1.50'),
(3, 1, 2, 3, '11.40', '3.80'),
(4, 1, 6, 11, '22.00', '2.00');

-- --------------------------------------------------------

--
-- Structure de la table `demandes_camion`
--

CREATE TABLE `demandes_camion` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom_camion` varchar(100) NOT NULL,
  `numero_permis` varchar(20) NOT NULL,
  `emplacement` varchar(200) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `menu` varchar(500) NOT NULL,
  `jours` varchar(200) NOT NULL,
  `etat` enum('en attente','validee','refusee') DEFAULT 'en attente',
  `date_demande` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `commentaire_admin` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `demandes_camion`
--

INSERT INTO `demandes_camion` (`id`, `user_id`, `nom_camion`, `numero_permis`, `emplacement`, `latitude`, `longitude`, `menu`, `jours`, `etat`, `date_demande`, `date_traitement`, `commentaire_admin`) VALUES
(1, 1, 'CACA braiser', '54121105257', 'bernay', '49.09022780', '0.59891570', 'pouller braiser', 'Lundi,Mercredi,Jeudi,Vendredi', 'validee', '2025-08-05 20:26:59', '2025-08-05 20:32:26', '');

-- --------------------------------------------------------

--
-- Structure de la table `entrepots`
--

CREATE TABLE `entrepots` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `adresse` text NOT NULL,
  `ville` varchar(100) NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `pays` varchar(50) DEFAULT 'France',
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `entrepots`
--

INSERT INTO `entrepots` (`id`, `nom`, `adresse`, `ville`, `code_postal`, `pays`, `telephone`, `email`, `responsable`, `date_creation`, `actif`, `latitude`, `longitude`) VALUES
(1, 'Entrepôt Central Paris', '15 Rue de Rivoli', 'Paris', '75000', 'France', '01 42 53 12 34', 'paris@drivncook.com', 'Jean Dupont', '2025-08-05 18:01:12', 1, '48.85593080', '2.35764460'),
(2, 'Entrepôt Lyon', '45 Avenue des Entreprises', 'Lyon', '69007', 'France', '04 78 92 15 67', 'lyon@drivncook.com', 'Marie Martin', '2025-08-05 18:01:12', 1, '45.76400000', '4.83570000'),
(3, 'Entrepôt Marseille', '23 Boulevard Industrial', 'Marseille', '13008', 'France', '04 91 45 78 90', 'marseille@drivncook.com', 'Pierre Durand', '2025-08-05 18:01:12', 1, '43.29650000', '5.36980000');

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `prix` decimal(10,2) NOT NULL,
  `categorie` enum('plat','boisson','dessert','accompagnement') DEFAULT 'plat',
  `ingredients` text,
  `allergenes` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `menu_produits`
--

CREATE TABLE `menu_produits` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite_necessaire` decimal(10,3) NOT NULL DEFAULT '1.000',
  `unite` enum('kg','litres','unites') DEFAULT 'unites'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `type` enum('aliment','boisson','préparé') DEFAULT 'aliment',
  `prix_unitaire` decimal(10,2) NOT NULL,
  `obligatoire` tinyint(1) DEFAULT '1',
  `quantite_minimale` int(11) DEFAULT '0',
  `reduction_fidelite` decimal(5,2) DEFAULT '0.00' COMMENT 'Réduction en pourcentage pour les clients fidèles (0-100)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `type`, `prix_unitaire`, `obligatoire`, `quantite_minimale`, `reduction_fidelite`) VALUES
(1, 'Pain artisanal', 'aliment', '1.50', 1, 5, '0.00'),
(2, 'Steak haché bio', 'aliment', '3.80', 1, 2, '0.00'),
(3, 'Fromage cheddar', 'aliment', '2.20', 1, 3, '0.00'),
(4, 'Salade iceberg', 'aliment', '1.00', 1, 2, '0.00'),
(5, 'Tomates cerises', 'aliment', '2.50', 1, 1, '0.00'),
(6, 'Coca-Cola 33cl', 'boisson', '2.00', 1, 10, '0.00'),
(7, 'Eau minérale 50cl', 'boisson', '1.00', 1, 20, '0.00'),
(8, 'Jus d orange 25cl', 'boisson', '2.50', 1, 5, '0.00'),
(9, 'Burger complet', 'préparé', '8.90', 0, 0, '0.00'),
(10, 'Hot-dog artisanal', 'préparé', '6.50', 0, 0, '0.00'),
(11, 'Salade César', 'préparé', '7.80', 0, 0, '0.00'),
(12, 'Frites maison', 'préparé', '3.50', 0, 0, '0.00');

-- --------------------------------------------------------

--
-- Structure de la table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `entrepot_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` decimal(10,3) NOT NULL DEFAULT '0.000',
  `unite` enum('kg','litres','unites') NOT NULL DEFAULT 'kg',
  `seuil_alerte` decimal(10,3) DEFAULT '10.000',
  `derniere_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `stocks`
--

INSERT INTO `stocks` (`id`, `entrepot_id`, `produit_id`, `quantite`, `unite`, `seuil_alerte`, `derniere_maj`) VALUES
(1, 1, 1, '500.000', 'kg', '50.000', '2025-08-05 18:01:12'),
(2, 1, 2, '200.000', 'kg', '20.000', '2025-08-05 18:01:12'),
(3, 1, 3, '145.000', 'kg', '15.000', '2025-08-05 18:48:07'),
(4, 1, 6, '1000.000', 'unites', '100.000', '2025-08-05 18:01:12'),
(5, 2, 1, '300.000', 'kg', '50.000', '2025-08-05 18:01:12'),
(6, 2, 2, '150.000', 'kg', '20.000', '2025-08-05 18:01:12'),
(7, 3, 1, '250.000', 'kg', '50.000', '2025-08-05 18:01:12'),
(8, 3, 6, '800.000', 'unites', '100.000', '2025-08-05 18:01:12'),
(9, 2, 9, '500.000', 'unites', '50.000', '2025-08-05 18:49:55');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `lieu_installation` varchar(200) DEFAULT NULL,
  `motivation` text,
  `numero_permis` varchar(20) DEFAULT NULL,
  `adresse` text,
  `role` enum('franchise') DEFAULT 'franchise',
  `statut` enum('en_attente','valide','refuse','desactive') DEFAULT 'en_attente',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `lieu_installation`, `motivation`, `numero_permis`, `adresse`, `role`, `statut`, `date_inscription`) VALUES
(1, 'Delphine', 'LEROUX', 'thomas.lejeune@gmail.com', '$2y$10$41GMU5e.iVaFZPjYtYBQ8eM37Xm2pw.1/8wx9jDkKsRFSqXFqvwpm', '0611571997', 'Paris, place de clichy', 'j adore le manger', '54121105257', NULL, 'franchise', 'valide', '2025-08-05 20:02:55');

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `camion_id` int(11) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `type_paiement` enum('especes','carte','cheque','virement') DEFAULT 'especes',
  `date_vente` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en_attente','valide') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `user_id`, `camion_id`, `montant`, `type_paiement`, `date_vente`) VALUES
(1, 1, NULL, '1.00', 'especes', '2025-08-05 21:01:12');

-- --------------------------------------------------------

--
-- Structure de la table `vente_details`
--

CREATE TABLE `vente_details` (
  `id` int(11) NOT NULL,
  `vente_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `vente_details`
--

INSERT INTO `vente_details` (`id`, `vente_id`, `produit_id`, `quantite`, `prix_unitaire`, `prix_total`) VALUES
(1, 1, 7, 1, '1.00', '1.00');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `camions`
--
ALTER TABLE `camions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `immatriculation` (`immatriculation`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `entrepot_id` (`entrepot_id`),
  ADD KEY `validee_par` (`validee_par`);

--
-- Index pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `demandes_camion`
--
ALTER TABLE `demandes_camion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `entrepots`
--
ALTER TABLE `entrepots`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `menu_produits`
--
ALTER TABLE `menu_produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_menu_produit` (`menu_id`,`produit_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stock` (`entrepot_id`,`produit_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `numero_permis` (`numero_permis`);

--
-- Index pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `camion_id` (`camion_id`);

--
-- Index pour la table `vente_details`
--
ALTER TABLE `vente_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vente_id` (`vente_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `camions`
--
ALTER TABLE `camions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `commande_details`
--
ALTER TABLE `commande_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `demandes_camion`
--
ALTER TABLE `demandes_camion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `entrepots`
--
ALTER TABLE `entrepots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `menu_produits`
--
ALTER TABLE `menu_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `vente_details`
--
ALTER TABLE `vente_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `camions`
--
ALTER TABLE `camions`
  ADD CONSTRAINT `camions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`entrepot_id`) REFERENCES `entrepots` (`id`),
  ADD CONSTRAINT `commandes_ibfk_3` FOREIGN KEY (`validee_par`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD CONSTRAINT `commande_details_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_details_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_camion`
--
ALTER TABLE `demandes_camion`
  ADD CONSTRAINT `demandes_camion_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menu_produits`
--
ALTER TABLE `menu_produits`
  ADD CONSTRAINT `menu_produits_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_produits_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`entrepot_id`) REFERENCES `entrepots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stocks_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventes_ibfk_2` FOREIGN KEY (`camion_id`) REFERENCES `camions` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `vente_details`
--
ALTER TABLE `vente_details`
  ADD CONSTRAINT `vente_details_ibfk_1` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vente_details_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
