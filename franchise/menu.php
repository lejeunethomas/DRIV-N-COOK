<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon menu</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/vente.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="menu.php" class="active">Mon menu</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content franchise">
            <div class="section-card franchise">
                <h2>Mon menu</h2>
            </div>

            <div class="menu-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>Gérer les plats</h3>
                    <button class="add-btn franchise" id="add-plat-btn">
                        + Ajouter un plat
                    </button>
                </div>

                <table id="menu-table">
                    <thead>
                        <tr>
                            <th>Nom du plat</th>
                            <th>Description</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menu-list">
                        <!-- Les plats seront chargés ici via JavaScript -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>