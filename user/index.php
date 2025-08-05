<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar.client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php" class="active">Accueil</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content.client">
            <h1>Bienvenue sur votre tableau de bord</h1>

            <div class="stats-grid" id="stats-client">
                <div class="stat-item info">
                    <div class="stat-number" id="nb-point" style="color: #1976d2;">...</div>
                    <div class="stat-label">Points de fidélité</div>
                </div>
            </div>

            <div class="quick-nav" style="margin-top: 1.5rem;">
                    <a href="commander.php" class="btn-nav" id="btn-commander" style="display:none;">
                        Commander
                    </a>
            </div>

            <div class="section-card client">
                <h2>Navigation rapide</h2>
                <div class="quick-nav">
                    <a href="mes_commandes.php" class="btn-nav"> Mes commandes</a>
                    <a href="newsletter.php" class="btn-nav"> Newsletter</a>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
