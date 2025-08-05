<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon menu - Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .category-separator {
            background: #f8f9fa !important;
        }
        .plat-indisponible {
            opacity: 0.6;
            background: #f5f5f5;
        }
        .badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
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
            <h1 style="color:#e64a19;">Gestion de mon menu</h1>
            
            <div class="section-card franchise">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2>Mon menu food truck</h2>
                        <p style="color: #666; margin: 0;">Gérez vos plats, boissons et accompagnements</p>
                    </div>
                    <button class="add-btn franchise" id="add-plat-btn">
                         + Ajouter un plat
                    </button>
                </div>

                <table id="menu-table">
                    <thead>
                        <tr>
                            <th>Nom du plat</th>
                            <th>Description & Ingrédients</th>
                            <th>Prix & Statut</th>
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
    
    <script src="../js/menu.js"></script>
</body>
</html>