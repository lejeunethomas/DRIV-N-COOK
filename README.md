# DRIV-N-COOK
Driv’n Cook – Système de gestion pour un réseau de franchises (projet académique)
Description :
Application web de gestion conçue dans le cadre d’un projet académique simulant les besoins d’un réseau de food trucks en franchise. Le système couvre les aspects essentiels de gestion opérationnelle : comptes franchisés, parc de camions, commandes de stock, ventes, et administration centrale.

Le projet vise à remplacer un système manuel (type tableurs Excel) par une plateforme centralisée, ergonomique, sécurisée et extensible.

Fonctionnalités principales :

🔐 Authentification et séparation des rôles (administrateur / franchisé)

🧾 Gestion des franchisés et affectation des camions

🚚 Suivi du parc de véhicules (état, entretiens, localisation)

📦 Système de commande de stock, avec contrôle automatique du respect du ratio d’achat imposé (≥80% des produits provenant du réseau)

📊 Suivi des ventes et génération automatique de rapports PDF

🖥️ Interface administrateur (back-office) et espace franchisé (front-office)

🌐 Déploiement sur serveur Apache (environnement Linux local)

Technologies utilisées :

Frontend : HTML5, CSS3, JavaScript (vanilla)

Backend : PHP (API REST)

Base de données : MySQL, avec phpMyAdmin

PDF : FPDF

Serveur web : Apache2 sur machine virtuelle Linux (déploiement local)

Structure du projet

/drivncook/
│
├── index.html                → page d'accueil
├── login.html                → interface de connexion
├── dashboard/                → pages back-office (admin)
├── franchise/                → espace utilisateur (franchisés)
├── api/                      → scripts PHP en REST (produits, utilisateurs, camions…)
├── includes/                 → connexion base de données et fonctions globales
├── css/                      → styles unifiés
├── js/                       → scripts front-end
├── pdf/                      → fichiers PDF générés
└── .htaccess                 → réécriture d'URL, gestion d’erreurs
