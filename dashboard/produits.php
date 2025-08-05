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
    <script src="../js/admin/stock-management.js"></script>
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
                        <th>État stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="products-table">
                </tbody>
            </table>
        </main>
    </div>

    <script>
        // Variables spécifiques à la page produits
        let products = [];
        let entrepots = [];
        let stocks = [];

        // Chargement initial
        document.addEventListener('DOMContentLoaded', function() {
            loadAllData();
            startGlobalMonitoring();
        });

        async function loadAllData() {
            // Utiliser les fonctions communes
            await loadAllStockData();
            
            // Récupérer les données depuis le module commun
            products = globalStockData.products;
            entrepots = globalStockData.entrepots;
            stocks = globalStockData.stocks;
            
            displayProducts();
        }

        function displayProducts() {
            const tbody = document.getElementById('products-table');
            tbody.innerHTML = '';

            products.forEach(product => {
                const typeClass = `type-${product.type}`;
                const obligatoireClass = product.obligatoire ? 'obligatoire-oui' : 'obligatoire-non';
                const obligatoireText = product.obligatoire ? 'Oui' : 'Non';
                
                const productStocks = stocks.filter(s => s.produit_id == product.id);
                const stockStatus = getStockStatusBadge(productStocks);
                
                tbody.innerHTML += `
                    <tr>
                        <td>${product.id}</td>
                        <td><strong>${product.nom}</strong>
                            ${product.quantite_minimale > 0 ? `<br><small style="color: #ff5722;">Min: ${product.quantite_minimale}</small>` : ''}
                        </td>
                        <td><span class="type-badge ${typeClass}">${product.type}</span></td>
                        <td>${formatPrice(product.prix_unitaire)}</td>
                        <td><span class="${obligatoireClass}">${obligatoireText}</span></td>
                        <td>${stockStatus}</td>
                        <td>
                            <button class="btn-action" onclick="editProduct(${product.id})">Modifier</button>
                            <button class="btn-action" onclick="viewProductStocks(${product.id})">Voir stocks</button>
                            <button class="btn-action" onclick="showProductModal(${product.id})">Dupliquer</button>
                            <button class="btn-action danger" onclick="deleteProduct(${product.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        // Fonctions spécifiques à la page produits
        function showAddProductModal() {
            showProductModal();
        }

        function editProduct(id) {
            const product = products.find(p => p.id == id);
            if (product) {
                showProductModal(product);
            }
        }

        function showProductModal(product = null) {
            const isEdit = product !== null;
            const title = isEdit ? 'Modifier le produit' : 'Ajouter un produit';
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 700px;">
                    <h3>${title}</h3>
                    <form id="product-form">
                        <div class="form-group">
                            <label for="nom">Nom du produit *</label>
                            <input type="text" id="nom" name="nom" required value="${product?.nom || ''}">
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="type">Type *</label>
                                <select id="type" name="type" required onchange="updateUniteOptions()">
                                    <option value="aliment" ${product?.type === 'aliment' ? 'selected' : ''}>Aliment</option>
                                    <option value="boisson" ${product?.type === 'boisson' ? 'selected' : ''}>Boisson</option>
                                    <option value="préparé" ${product?.type === 'préparé' ? 'selected' : ''}>Plat préparé</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="prix_unitaire">Prix unitaire (€) *</label>
                                <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" required value="${product?.prix_unitaire || ''}">
                            </div>
                        </div>
                        
                        <!-- Section produit obligatoire -->
                        <div class="form-group" style="border: 1px solid #ddd; padding: 1rem; border-radius: 6px; background: #f8f9fa;">
                            <div class="checkbox-group">
                                <input type="checkbox" id="obligatoire" name="obligatoire" ${product?.obligatoire ? 'checked' : ''} onchange="toggleQuantiteMinimale()">
                                <label for="obligatoire">Produit obligatoire (pour avantages fidélité)</label>
                            </div>
                            
                            <div id="quantite-minimale-container" style="margin-top: 1rem; ${product?.obligatoire ? '' : 'display: none;'}">
                                <label for="quantite_minimale">Quantité minimale à commander *</label>
                                <input type="number" id="quantite_minimale" name="quantite_minimale" min="0" step="1" value="${product?.quantite_minimale || 0}">
                                <small style="color: #666;">Si 0, aucune quantité minimale n'est imposée</small>
                            </div>
                        </div>
                        
                        ${!isEdit ? `
                        <hr style="margin: 2rem 0; border: 1px solid #ddd;">
                        <h4 style="color: #1976d2; margin-bottom: 1rem;">Stocks initiaux dans les entrepôts</h4>
                        <div id="stocks-container">
                            ${entrepots.map(entrepot => `
                                <div class="stock-item" style="border: 1px solid #ddd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                    <h5 style="margin: 0 0 1rem 0; color: #333;">${entrepot.nom}</h5>
                                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label>Quantité initiale</label>
                                            <input type="number" name="stock_${entrepot.id}_quantite" step="0.001" min="0" value="0" style="padding: 0.5rem;">
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label>Unité</label>
                                            <select name="stock_${entrepot.id}_unite" class="unite-select" style="padding: 0.5rem;">
                                                <option value="kg">Kilogrammes</option>
                                                <option value="litres">Litres</option>
                                                <option value="unites">Unités</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label>Seuil d'alerte</label>
                                            <input type="number" name="stock_${entrepot.id}_seuil" step="0.001" min="0" value="10" style="padding: 0.5rem;">
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        ` : ''}
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter avec stocks'}</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            updateUniteOptions();
            
            document.getElementById('product-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.obligatoire = document.getElementById('obligatoire').checked;
                data.quantite_minimale = document.getElementById('quantite_minimale').value || 0;
                
                if (isEdit) {
                    data.id = product.id;
                    await updateProduct(data);
                } else {
                    data.stocks = [];
                    entrepots.forEach(entrepot => {
                        const quantite = parseFloat(formData.get(`stock_${entrepot.id}_quantite`)) || 0;
                        if (quantite > 0) {
                            data.stocks.push({
                                entrepot_id: entrepot.id,
                                quantite: quantite,
                                unite: formData.get(`stock_${entrepot.id}_unite`),
                                seuil_alerte: parseFloat(formData.get(`stock_${entrepot.id}_seuil`)) || 10
                            });
                        }
                    });
                    
                    await addProductWithStocks(data);
                }
            };
        }

        function toggleQuantiteMinimale() {
            const obligatoire = document.getElementById('obligatoire').checked;
            const container = document.getElementById('quantite-minimale-container');
            const input = document.getElementById('quantite_minimale');
            
            if (obligatoire) {
                container.style.display = 'block';
                input.required = true;
            } else {
                container.style.display = 'none';
                input.required = false;
                input.value = 0;
            }
        }

        async function addProductWithStocks(data) {
            try {
                const productResponse = await fetch('../api/produits/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        nom: data.nom,
                        type: data.type,
                        prix_unitaire: data.prix_unitaire,
                        obligatoire: data.obligatoire
                    })
                });
                const productResult = await productResponse.json();
                
                if (!productResult.success) {
                    showAlert('Erreur lors de la création du produit: ' + productResult.message, 'error');
                    return;
                }
                
                const productId = productResult.id;
                
                let stocksCreated = 0;
                for (const stock of data.stocks) {
                    try {
                        const stockResponse = await fetch('../api/stocks/update.php', {
                            method: 'PUT',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                entrepot_id: stock.entrepot_id,
                                produit_id: productId,
                                quantite: stock.quantite,
                                unite: stock.unite,
                                seuil_alerte: stock.seuil_alerte
                            })
                        });
                        const stockResult = await stockResponse.json();
                        
                        if (stockResult.success) {
                            stocksCreated++;
                        }
                    } catch (error) {
                        console.error('Erreur lors de la création du stock:', error);
                    }
                }
                
                showAlert(
                    `Produit créé avec succès ! ${stocksCreated} stock(s) ajouté(s) dans ${data.stocks.length} entrepôt(s).`, 
                    'success'
                );
                closeModal();
                await loadAllData();
                
            } catch (error) {
                showAlert('Erreur réseau lors de la création', 'error');
                console.error('Erreur:', error);
            }
        }

        async function updateProduct(data) {
            try {
                const response = await fetch('../api/produits/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit mis à jour avec succès !', 'success');
                    closeModal();
                    await loadAllData();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la mise à jour', 'error');
                console.error('Erreur:', error);
            }
        }

        async function deleteProduct(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/produits/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit supprimé avec succès !', 'success');
                    await loadAllData();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la suppression', 'error');
                console.error('Erreur:', error);
            }
        }

        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>