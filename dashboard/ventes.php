<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des ventes - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/vente.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php" class="active">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content admin">
            <h1 class="admin">Gestion des ventes</h1>

            <!-- Navigation par onglets -->
            <div class="tabs">
                <button class="tab-button active" data-tab="ventes" onclick="showTab('ventes')">
                    Toutes les ventes
                </button>
                <button class="tab-button" data-tab="produits" onclick="showTab('produits')">
                    Produits vendus
                </button>
            </div>

            <!-- Statistiques générales -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-item success">
                    <div class="stat-number" id="total-ventes" style="color: #4caf50;">0€</div>
                    <div class="stat-label">Ventes totales</div>
                </div>
                <div class="stat-item info">
                    <div class="stat-number" id="ventes-jour" style="color: #2196f3;">0€</div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-item warning">
                    <div class="stat-number" id="ventes-semaine" style="color: #ff9800;">0€</div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                <div class="stat-item primary">
                    <div class="stat-number" id="ventes-mois" style="color: #1976d2;">0€</div>
                    <div class="stat-label">Ce mois</div>
                </div>
            </div>

            <!-- Onglet Ventes -->
            <div id="tab-ventes" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Liste des ventes</h3>
                    <button class="add-btn" id="add-ventes-btn">+ Enregistrer une vente</button>
                </div>
                
                <!-- Filtres -->
                <div class="filters" style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                    <label for="filter-camion">Camion :</label>
                    <select id="filter-camion" onchange="filterVentes()">
                        <option value="">Tous les camions</option>
                    </select>
                    
                    <label for="filter-periode">Période :</label>
                    <select id="filter-periode" onchange="filterVentes()">
                        <option value="">Toutes les périodes</option>
                        <option value="aujourdhui">Aujourd'hui</option>
                        <option value="semaine">Cette semaine</option>
                        <option value="mois">Ce mois</option>
                    </select>
                </div>
                
                <table id="ventes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Camion</th>
                            <th>Franchisé</th>
                            <th>Produits</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Onglet Produits -->
            <div id="tab-produits" class="tab-content">
                <h3>Produits les plus vendus</h3>
                <table id="produits-vendus-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité totale</th>
                            <th>Chiffre d'affaires</th>
                            <th>Nombre de ventes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Configuration spécifique Admin
        document.addEventListener('DOMContentLoaded', function() {
            // Charger les données initiales
            loadAdminVentesData();
            loadCamionsFilter();
        });

        // Fonction de filtrage des ventes
        function filterVentes() {
            const camion = document.getElementById('filter-camion').value;
            const periode = document.getElementById('filter-periode').value;
            
            // Recharger les ventes avec les filtres
            loadAdminVentesData(camion, periode);
        }

        // Gestion des onglets
        function showTab(tabName) {
            // Cacher tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(`tab-${tabName}`).classList.add('active');
            event.target.classList.add('active');
        }

        // Chargement des données admin (différent des franchisés)
        async function loadAdminVentesData(camion = '', periode = '') {
            try {
                let url = '../api/ventes/list_admin.php';
                const params = new URLSearchParams();
                if (camion) params.append('camion', camion);
                if (periode) params.append('periode', periode);
                if (params.toString()) url += '?' + params.toString();

                const response = await fetch(url);
                const ventes = await response.json();
                
                displayAdminVentesTable(ventes);
            } catch (error) {
                console.error('Erreur lors du chargement des ventes admin:', error);
            }
        }

        function displayAdminVentesTable(ventes) {
            const tbody = document.querySelector('#ventes-table tbody');
            tbody.innerHTML = '';
            
            ventes.forEach(vente => {
                tbody.innerHTML += `
                    <tr>
                        <td>#${vente.id}</td>
                        <td>${vente.camion_nom || 'N/A'}</td>
                        <td>${vente.franchisé_nom || 'N/A'}</td>
                        <td>${vente.produits_resume || 'N/A'}</td>
                        <td><strong>${parseFloat(vente.montant).toFixed(2)}€</strong></td>
                        <td>${new Date(vente.date_vente).toLocaleString('fr-FR')}</td>
                        <td class="actions">
                            <button class="btn-action" onclick="viewVenteDetails(${vente.id})">Détails</button>
                            <button class="btn-action danger" onclick="deleteVenteAdmin(${vente.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        async function loadCamionsFilter() {
            try {
                const response = await fetch('../api/camions/list.php');
                const camions = await response.json();
                
                const select = document.getElementById('filter-camion');
                camions.forEach(camion => {
                    const option = document.createElement('option');
                    option.value = camion.id;
                    option.textContent = `${camion.nom} - ${camion.localisation}`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Erreur lors du chargement des camions:', error);
            }
        }
    </script>
</body>
</html>