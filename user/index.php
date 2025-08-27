<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil Client - DRIV'N'COOK</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php" class="active">Accueil</a>
            <a href="commander.php">Commander</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content client">
            <h1 class="client">Bienvenue sur votre espace client</h1>

            <div class="stats-grid" id="stats-client">
                <div class="stat-item info">
                    <div class="stat-number" id="nb-commandes" style="color: #1976d2;">0</div>
                    <div class="stat-label">Commandes passées</div>
                </div>
                <div class="stat-item success">
                    <div class="stat-number" id="nb-point" style="color: #4caf50;">0</div>
                    <div class="stat-label">Points de fidélité</div>
                </div>
            </div>

            <div class="section-card client"  style="margin-top: 1.5rem;">
                <h2>Navigation rapide</h2>
                <div class="quick-nav">
                    <a href="commander.php" class="btn-nav">Commander</a>
                    <a href="mes_commandes.php" class="btn-nav">Mes commandes</a>
                    <a href="newsletter.php" class="btn-nav">Newsletter</a>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Charger les statistiques client
        async function loadClientStats() {
            try {
                const response = await fetch('../api/ventes/stats.php');
                const stats = await response.json();

                document.getElementById('nb-commandes').textContent = stats.nb_commandes !== undefined
                    ? stats.nb_commandes
                    : '0';

                document.getElementById('nb-point').textContent = stats.nb_commandes !== undefined
                    ? stats.nb_commandes
                    : '0';

            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadClientStats);
    </script>
</body>
</html>
