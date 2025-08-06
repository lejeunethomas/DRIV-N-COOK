<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des produits - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        td .badge {
            position: static !important;
            display: inline-block !important;
            margin: 0 !important;
        }
        
        .badge.badge-warning {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .badge.badge-warning:hover {
            background-color: #f57c00 !important;
        }
        
        td {
            position: relative;
            overflow: visible;
        }
        
        td[style*="text-align: center"] {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php" class="active">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content admin">
            <h1 class="admin">Gestion des produits</h1>
            
            <!-- Navigation rapide -->
            <div class="quick-nav">
                <a href="#" class="btn-nav current">Produits</a>
                <a href="entrepots.php" class="btn-nav">Entrepôts</a>
                <a href="commandes.php" class="btn-nav">Commandes</a>
            </div>
            
            <!-- Vue d'ensemble globale -->
            <div class="global-overview">
                <h3 style="margin: 0 0 1rem 0; color: #1976d2;">Vue d'ensemble du système</h3>
                <div class="stats-grid">
                    <div class="stat-item critical">
                        <div class="stat-number" id="global-ruptures" style="color: #f44336;">0</div>
                        <div class="stat-label">Ruptures</div>
                    </div>
                    <div class="stat-item warning">
                        <div class="stat-number" id="global-alertes" style="color: #ff9800;">0</div>
                        <div class="stat-label">Alertes</div>
                    </div>
                    <div class="stat-item success">
                        <div class="stat-number" id="global-stocks-ok" style="color: #4caf50;">0</div>
                        <div class="stat-label">Stocks OK</div>
                    </div>
                    <div class="stat-item info">
                        <div class="stat-number" id="global-entrepots" style="color: #2196f3;">0</div>
                        <div class="stat-label">Entrepôts</div>
                    </div>
                    <div class="stat-item info">
                        <div class="stat-number" id="global-produits" style="color: #2196f3;">0</div>
                        <div class="stat-label">Produits</div>
                    </div>
                </div>
            </div>
            
            <button class="add-btn" onclick="showAddProductModal()">+ Ajouter un produit</button>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom du produit</th>
                        <th>Type</th>
                        <th>Prix unitaire</th>
                        <th>Obligatoire</th>
                        <th>Réduction fidélité</th>
                        <th>État stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="products-table">
                </tbody>
            </table>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script src="../js/admin/stock-management.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            await loadAllStockData();
            displayProducts();
        });

        // FONCTION D'AFFICHAGE COMPLÈTE
        function displayProducts() {
            const tbody = document.getElementById('products-table');
            tbody.innerHTML = '';
            
            if (!Array.isArray(globalStockData.products) || globalStockData.products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">Aucun produit trouvé</td></tr>';
                return;
            }
            
            globalStockData.products.forEach(product => {
                const row = buildProductRow(product);
                tbody.appendChild(row);
            });
        }

        // CONSTRUCTION DE LIGNE COMPLÈTE
        function buildProductRow(product) {
            const row = document.createElement('tr');
            
            const productStocks = globalStockData.stocks.filter(s => s.produit_id == product.id);
            const stockStatusBadge = getStockStatusBadge(productStocks);
            
            const obligatoireBadge = product.obligatoire ? 
                '<span class="badge badge-success">Partenaire</span>' : 
                '<span class="badge badge-secondary">Standard</span>';
            
            const reductionText = product.reduction_fidelite > 0 ? 
                `${product.reduction_fidelite}%` : 
                'Aucune';
            
            row.innerHTML = `
                <td>${product.id}</td>
                <td>
                    <strong>${product.nom}</strong>
                    ${product.quantite_minimale > 0 ? `<br><small style="color: #666;">Quantité min: ${product.quantite_minimale}</small>` : ''}
                </td>
                <td><span class="type-badge type-${product.type}">${product.type}</span></td>
                <td><strong>${AdminCommon.utils.formatPrice(product.prix_unitaire)}</strong></td>
                <td style="text-align: center;">${obligatoireBadge}</td>
                <td style="text-align: center;">${reductionText}</td>
                <td style="text-align: center;">
                    <div onclick="viewProductStocks(${product.id})" style="cursor: pointer;">
                        ${stockStatusBadge}
                    </div>
                </td>
                <td>
                    <button class="btn-action" onclick="editProduct(${product.id})">Modifier</button>
                    <button class="btn-action" onclick="viewProductStocks(${product.id})" title="Voir les stocks">📊</button>
                    <button class="btn-action danger" onclick="deleteProduct(${product.id})">Supprimer</button>
                </td>
            `;
            return row;
        }

        // MODAL D'AJOUT AVEC TOUS LES CHAMPS
        function showAddProductModal() {
            AdminCommon.utils.createFormModal({
                title: 'Ajouter un nouveau produit',
                fields: [
                    { name: 'nom', label: 'Nom du produit', type: 'text', required: true, 
                      attributes: 'placeholder="Ex: Coca-Cola, Pain, Salade..."' },
                    { name: 'type', label: 'Type', type: 'select', required: true, 
                      options: [
                          { value: '', text: 'Sélectionner un type' },
                          { value: 'aliment', text: 'Aliment' },
                          { value: 'boisson', text: 'Boisson' },
                          { value: 'préparé', text: 'Plat préparé' }
                      ]
                    },
                    { name: 'prix_unitaire', label: 'Prix unitaire (€)', type: 'number', required: true, 
                      attributes: 'step="0.01" min="0" placeholder="Ex: 2.50"' },
                    { name: 'obligatoire', label: 'Produit partenaire', type: 'checkbox', 
                      help: 'Les produits partenaires offrent des réductions aux clients' },
                    { name: 'quantite_minimale', label: 'Quantité minimale de commande', type: 'number', 
                      attributes: 'min="0" step="1" placeholder="0 = pas de minimum"',
                      help: 'Quantité minimale que les franchisés doivent commander' },
                    { name: 'reduction_fidelite', label: 'Réduction fidélité (%)', type: 'number', 
                      attributes: 'min="0" max="100" step="0.1" placeholder="Ex: 5.0"',
                      help: 'Réduction accordée aux clients fidèles' }
                ],
                onSubmit: async (data, isEdit) => {
                    data.obligatoire = data.obligatoire ? 1 : 0;
                    data.quantite_minimale = parseInt(data.quantite_minimale) || 0;
                    data.reduction_fidelite = parseFloat(data.reduction_fidelite) || 0;
                    
                    const success = await AdminCommon.utils.saveData('../api/produits/add.php', data);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadAllStockData();
                        displayProducts();
                    }
                }
            });
        }

        // MODAL DE MODIFICATION COMPLÈTE
        function editProduct(id) {
            const product = globalStockData.products.find(p => p.id == id);
            if (!product) {
                AdminCommon.utils.showAlert('Produit non trouvé', 'error');
                return;
            }

            AdminCommon.utils.createFormModal({
                title: `Modifier le produit #${id}`,
                data: product,
                fields: [
                    { name: 'nom', label: 'Nom du produit', type: 'text', required: true },
                    { name: 'type', label: 'Type', type: 'select', required: true, 
                      options: [
                          { value: 'aliment', text: 'Aliment' },
                          { value: 'boisson', text: 'Boisson' },
                          { value: 'préparé', text: 'Plat préparé' }
                      ]
                    },
                    { name: 'prix_unitaire', label: 'Prix unitaire (€)', type: 'number', required: true, 
                      attributes: 'step="0.01" min="0"' },
                    { name: 'obligatoire', label: 'Produit partenaire', type: 'checkbox' },
                    { name: 'quantite_minimale', label: 'Quantité minimale de commande', type: 'number', 
                      attributes: 'min="0" step="1"' },
                    { name: 'reduction_fidelite', label: 'Réduction fidélité (%)', type: 'number', 
                      attributes: 'min="0" max="100" step="0.1"' }
                ],
                onSubmit: async (data, isEdit) => {
                    data.obligatoire = data.obligatoire ? 1 : 0;
                    data.quantite_minimale = parseInt(data.quantite_minimale) || 0;
                    data.reduction_fidelite = parseFloat(data.reduction_fidelite) || 0;
                    
                    const success = await AdminCommon.utils.saveData('../api/produits/update.php', data, true);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadAllStockData();
                        displayProducts();
                    }
                }
            });
        }

        // FONCTION DE SUPPRESSION
        async function deleteProduct(id) {
            const success = await AdminCommon.utils.deleteData(
                '../api/produits/delete.php',
                { id },
                'Êtes-vous sûr de vouloir supprimer ce produit ? Cette action supprimera également tous les stocks associés.'
            );
            
            if (success) {
                await loadAllStockData();
                displayProducts();
            }
        }

    </script>
</body>
</html>