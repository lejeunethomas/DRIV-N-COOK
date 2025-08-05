<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Newsletter</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar.client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php" class="active">Newsletter</a>
            <form action="../api/users/logout.php" method="post">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content.client">
            <h1>Newsletter</h1>

            <div class="section-card client">
                <h2>Inscription à la Newsletter</h2>
                <p>Recevez les dernières nouvelles et offres spéciales directement dans votre boîte mail.</p>
                <form action="../api/newsletter/subscribe.php" method="post">
                    <input type="email" name="email" placeholder="Votre email" required>
                    <button type="submit" class="btn-nav">S'inscrire</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>