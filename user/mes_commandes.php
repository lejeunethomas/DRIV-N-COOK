<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Commandes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar.client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="mes_commandes.php" class="active">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content.client">
            <h1>Mes Commandes</h1>

            <div class="section-card client">
                <h2>Historique des commandes</h2>
                <div class="quick-nav" style="margin-top: 1.5rem;">
                    <a href="commander.php" class="btn-nav" id="btn-commander" style="display:none;">
                        Commander
                    </a>
                </div>
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th>Descriptif</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="commandes-list">
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>