<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes ventes - Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/vente.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php" class="active">Mes ventes</a>
            <a href="menu.php">Mon menu</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content franchise">
            <div class="section-card franchise">
                <h2>💰 Mes ventes du jour</h2>
                <p>Enregistrez vos ventes rapidement et suivez vos performances</p>
            </div>

            <!-- Statistiques personnelles -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-item success">
                    <div class="stat-number" id="total-ventes" style="color: #4caf50;">0€</div>
                    <div class="stat-label">Total général</div>
                </div>
                <div class="stat-item info">
                    <div class="stat-number" id="Ventes-jour" style="color: #2196f3;">0€</div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-item warning">
                    <div class="stat-number" id="Ventes-semaine" style="color: #ff9800;">0€</div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                <div class="stat-item primary">
                    <div class="stat-number" id="Ventes-mois" style="color: #e64a19;">0€</div>
                    <div class="stat-label">Ce mois</div>
                </div>
            </div>

            <!-- Interface de saisie -->
            <div class="ventes-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>🛒 Saisie rapide de vente</h3>
                    <button class="add-btn franchise" id="add-ventes-btn">
                        + Nouvelle vente
                    </button>
                </div>

                <!-- Tableau des ventes du jour -->
                <table id="ventes-table">
                    <thead>
                        <tr>
                            <th>Produits vendus</th>
                            <th>Quantité totale</th>
                            <th>Montant</th>
                            <th>Heure</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadVentesStats();
            loadVentesAujourdhui();
            loadProduits();
        });
    </script>
</body>
</html>

