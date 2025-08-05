<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin - DRIV'N'COOK</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php" class="active">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content admin">
            <h1 class="admin" style="text-align:center; margin-bottom:2rem;">Tableau de bord Administrateur</h1>
            
            <!-- Statistiques générales -->
            <div class="stats-grid" style="margin-bottom: 3rem;">
                <div class="stat-item info">
                    <div class="stat-number" id="nb-franchise" style="color: #1976d2;">...</div>
                    <div class="stat-label">Franchisés</div>
                </div>
                <div class="stat-item info">
                    <div class="stat-number" id="nb-camion" style="color: #1976d2;">...</div>
                    <div class="stat-label">Camions en service</div>
                </div>
                <div class="stat-item success">
                    <div class="stat-number" id="nb-vente" style="color: #4caf50;">...</div>
                    <div class="stat-label">Ventes totales</div>
                </div>
                <div class="stat-item warning">
                    <div class="stat-number" id="nb-commande" style="color: #ff9800;">...</div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
            
            <!-- Statistiques des demandes en attente -->
            <div class="section-card admin">
                <h2>Éléments nécessitant votre attention</h2>
                <div class="stats-grid">
                    <div class="stat-item warning" id="pending-franchises">
                        <div class="stat-number" id="nb-pending-franchises" style="color: #ff9800;">...</div>
                        <div class="stat-label">Comptes franchisés en attente</div>
                    </div>
                    <div class="stat-item critical" id="pending-trucks">
                        <div class="stat-number" id="nb-pending-trucks" style="color: #f44336;">...</div>
                        <div class="stat-label">Demandes de camions</div>
                    </div>
                    <div class="stat-item warning" id="pending-orders">
                        <div class="stat-number" id="nb-pending-orders" style="color: #ff9800;">...</div>
                        <div class="stat-label">Commandes en attente</div>
                    </div>
                    <div class="stat-item critical" id="stock-alerts">
                        <div class="stat-number" id="nb-stock-alerts" style="color: #f44336;">...</div>
                        <div class="stat-label">Alertes de stock</div>
                    </div>
                </div>
                
                <!-- Actions rapides pour les alertes -->
                <div class="quick-nav" style="margin-top: 1.5rem;">
                    <a href="franchisés.php" class="btn-nav" id="btn-pending-franchises" style="display:none;">
                        Valider les comptes
                    </a>
                    <a href="camions.php" class="btn-nav" id="btn-pending-trucks" style="display:none;">
                        Traiter les demandes
                    </a>
                    <a href="commandes.php" class="btn-nav" id="btn-pending-orders" style="display:none;">
                        Valider les commandes
                    </a>
                    <a href="entrepots.php" class="btn-nav" id="btn-stock-alerts" style="display:none;">
                        Gérer les stocks
                    </a>
                </div>
            </div>
            
            <!-- Navigation rapide vers les sections -->
            <div class="section-card admin">
                <h2>Navigation rapide</h2>
                <div class="quick-nav">
                    <a href="franchisés.php" class="btn-nav"> Gérer les franchisés</a>
                    <a href="camions.php" class="btn-nav"> Gérer les camions</a>
                    <a href="produits.php" class="btn-nav"> Gérer les produits</a>
                    <a href="entrepots.php" class="btn-nav"> Gérer les entrepôts</a>
                    <a href="ventes.php" class="btn-nav"> Voir les ventes</a>
                    <a href="commandes.php" class="btn-nav"> Voir les commandes</a>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Charger les statistiques principales
        async function loadMainStats() {
            try {
                // Franchisés
                const franchisesRes = await fetch('../api/users/get_all.php');
                const franchises = await franchisesRes.json();
                document.getElementById('nb-franchise').textContent = franchises.length || '0';

                // Camions
                const camionsRes = await fetch('../api/camions/get_by_user.php?admin=1');
                const camions = await camionsRes.json();
                document.getElementById('nb-camion').textContent = camions.length || '0';

                // Ventes
                const ventesRes = await fetch('../api/ventes/add.php?stats=1');
                const ventes = await ventesRes.json();
                document.getElementById('nb-vente').textContent = ventes.total || '0€';

                // Commandes
                const commandesRes = await fetch('../api/commandes/list_by_user.php?admin=1');
                const commandes = await commandesRes.json();
                document.getElementById('nb-commande').textContent = commandes.length || '0';
                
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
            }
        }

        // Charger les alertes et éléments nécessitant attention
        async function loadAlerts() {
            try {
                // Comptes franchisés en attente
                const pendingFranchisesRes = await fetch('../api/users/validation.php');
                const pendingFranchises = await pendingFranchisesRes.json();
                const nbPendingFranchises = pendingFranchises.length || 0;
                document.getElementById('nb-pending-franchises').textContent = nbPendingFranchises;
                document.getElementById('btn-pending-franchises').style.display = nbPendingFranchises > 0 ? 'inline-flex' : 'none';

                // Demandes de camions
                const pendingTrucksRes = await fetch('../api/camions/demandes.php');
                const pendingTrucks = await pendingTrucksRes.json();
                const nbPendingTrucks = pendingTrucks.length || 0;
                document.getElementById('nb-pending-trucks').textContent = nbPendingTrucks;
                document.getElementById('btn-pending-trucks').style.display = nbPendingTrucks > 0 ? 'inline-flex' : 'none';

                // Commandes en attente
                const pendingOrdersRes = await fetch('../api/commandes/list_all.php?statut=en_attente');
                const pendingOrders = await pendingOrdersRes.json();
                const nbPendingOrders = pendingOrders.length || 0;
                document.getElementById('nb-pending-orders').textContent = nbPendingOrders;
                document.getElementById('btn-pending-orders').style.display = nbPendingOrders > 0 ? 'inline-flex' : 'none';

                // Alertes de stock
                const stocksRes = await fetch('../api/stocks/list.php');
                const stocks = await stocksRes.json();
                const stockAlerts = stocks.filter(s => s.quantite == 0 || s.alerte == 1).length || 0;
                document.getElementById('nb-stock-alerts').textContent = stockAlerts;
                document.getElementById('btn-stock-alerts').style.display = stockAlerts > 0 ? 'inline-flex' : 'none';

            } catch (error) {
                console.error('Erreur lors du chargement des alertes:', error);
            }
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadMainStats();
            loadAlerts();

            // Actualisation automatique des alertes toutes les 30 secondes
            setInterval(() => {
                loadAlerts();
            }, 30000);
        });
    </script>
</body>
</html>