<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar.client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php" class="active">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content.client">
            <h1>Mon Profil</h1>

            <div class="section-card client">
                <h2>Informations personnelles</h2>
                <p>Nom : John Doe</p>
                <p>Email : john.doe@example.com</p>
            </div>
        </main>
    </div>
</body>
</html>
