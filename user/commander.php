<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commander</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content client">
            <h1>Commander</h1>

            <div class="section-card client">
                <h2>Passer une commande</h2>
                <p>Choisissez vos plats et passez votre commande en quelques clics.</p>
            </div>

            <table id="camion-table">
                <thead>
                    <tr>
                        <th>Food Truck</th>
                        <th>Position</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="camion-list">
                </tbody>
            </table>
        </main>
    </div>

    <script src="../js/commande.js"></script>