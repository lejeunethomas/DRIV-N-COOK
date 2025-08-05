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
    
    <!-- AJOUTEZ CE CSS -->
    <style>
        /* Forcer les badges à rester dans leur cellule */
        td .badge {
            position: static !important;
            display: inline-block !important;
            margin: 0 !important;
        }
        
        /* Style spécifique pour les badges cliquables */
        .badge.badge-warning {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .badge.badge-warning:hover {
            background-color: #f57c00 !important;
        }
        
        /* Assurer que les cellules du tableau contiennent leurs éléments */
        td {
            position: relative;
            overflow: visible;
        }
        
        /* Style pour les cellules de réduction */
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
                
                // Badge de réduction fidélité
                const reductionBadge = product.obligatoire && product.reduction_fidelite > 0 ? 
                    `<span class="badge badge-success" title="Réduction fidélité active">-${product.reduction_fidelite}%</span>` :
                    product.obligatoire ? 
                    `<span class="badge badge-warning" title="Pas de réduction définie" onclick="editReduction(${product.id})">Définir réduction</span>` :
                    '<span class="badge badge-secondary">N/A</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td>${product.id}</td>
                        <td><strong>${product.nom}</strong>
                            ${product.quantite_minimale > 0 ? `<br><small style="color: #ff5722;">Min: ${product.quantite_minimale}</small>` : ''}
                        </td>
                        <td><span class="type-badge ${typeClass}">${product.type}</span></td>
                        <td>${formatPrice(product.prix_unitaire)}</td>
                        <td><span class="${obligatoireClass}">${obligatoireText}</span></td>
                        <td style="text-align: center;">${reductionBadge}</td>
                        <td>${stockStatus}</td>
                        <td>
                            <button class="btn-action" onclick="editProduct(${product.id})">Modifier</button>
                            ${product.obligatoire ? `<button class="btn-action" onclick="editReduction(${product.id})">Réduction</button>` : ''}
                            <button class="btn-action" onclick="viewProductStocks(${product.id})">Voir stocks</button>
                            <button class="btn-action danger" onclick="deleteProduct(${product.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        // Fonction pour éditer la réduction fidélité
        async function editReduction(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;
            
            if (!product.obligatoire) {
                showAlert('Seuls les produits obligatoires peuvent avoir des réductions fidélité', 'warning');
                return;
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Réduction fidélité - ${product.nom}</h3>
                    
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #1976d2;">
                        <p style="margin: 0; color: #1976d2; font-weight: 500;">
                            <strong>Produit partenaire :</strong> ${product.nom}<br>
                            <strong>Prix de base :</strong> ${parseFloat(product.prix_unitaire).toFixed(2)}€<br>
                            <strong>Réduction actuelle :</strong> ${product.reduction_fidelite || 0}%
                        </p>
                    </div>
                    
                    <form id="reduction-form">
                        <div class="form-group">
                            <label for="reduction_fidelite">Réduction fidélité (%) *</label>
                            <input type="number" id="reduction_fidelite" name="reduction_fidelite" 
                                   min="0" max="100" step="0.01" required
                                   value="${product.reduction_fidelite || 0}"
                                   placeholder="Ex: 15.00">
                            <small style="color: #666;">
                                Réduction accordée aux clients avec carte de fidélité (0-100%)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="commentaire">Commentaire (optionnel)</label>
                            <textarea id="commentaire" name="commentaire" rows="2" 
                                      placeholder="Raison de cette modification..."></textarea>
                        </div>
                        
                        <div id="preview-reduction" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                            <!-- Aperçu sera affiché ici -->
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Appliquer la réduction</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Mettre à jour l'aperçu en temps réel
            const reductionInput = document.getElementById('reduction_fidelite');
            const updatePreview = () => {
                const reduction = parseFloat(reductionInput.value) || 0;
                const prixBase = parseFloat(product.prix_unitaire);
                const prixReduit = prixBase * (1 - reduction / 100);
                const economie = prixBase - prixReduit;
                
                document.getElementById('preview-reduction').innerHTML = `
                    <h4 style="margin: 0 0 0.5rem 0; color: #1976d2;">Aperçu de la réduction :</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; text-align: center;">
                        <div>
                            <strong>Prix normal</strong><br>
                            <span style="font-size: 1.1rem;">${prixBase.toFixed(2)}€</span>
                        </div>
                        <div>
                            <strong>Prix fidélité</strong><br>
                            <span style="font-size: 1.1rem; color: #4caf50;">${prixReduit.toFixed(2)}€</span>
                        </div>
                        <div>
                            <strong>Économie</strong><br>
                            <span style="font-size: 1.1rem; color: #f44336;">-${economie.toFixed(2)}€</span>
                        </div>
                    </div>
                    ${reduction > 0 ? `
                        <div style="margin-top: 0.5rem; text-align: center; color: #4caf50; font-weight: 500;">
                            Les clients fidèles économiseront ${economie.toFixed(2)}€ sur ce produit
                        </div>
                    ` : `
                        <div style="margin-top: 0.5rem; text-align: center; color: #666;">
                            Aucune réduction appliquée
                        </div>
                    `}
                `;
            };
            
            reductionInput.addEventListener('input', updatePreview);
            updatePreview(); // Affichage initial
            
            // Gérer la soumission
            document.getElementById('reduction-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.produit_id = productId;
                
                await updateReduction(data);
            };
        }

        // Fonction pour mettre à jour la réduction
        async function updateReduction(data) {
            try {
                const response = await fetch('../api/produits/update_reduction.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(`${result.message}`, 'success');
                    closeModal();
                    await loadAllData();
                } else {
                    showAlert('Erreur : ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de la mise à jour de la réduction:', error);
                showAlert('Erreur réseau lors de la mise à jour', 'error');
            }
        }

        // Fonction pour voir l'historique des réductions
        async function viewHistoriqueReductions(productId = null) {
            try {
                const url = productId ? 
                    `../api/produits/historique_reductions.php?produit_id=${productId}` :
                    '../api/produits/historique_reductions.php';
                    
                const response = await fetch(url);
                const historique = await response.json();
                
                const modal = document.createElement('div');
                modal.className = 'modal modal-large';
                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${productId ? 'Historique du produit' : 'Historique global des réductions fidélité'}</h3>
                        
                        ${Array.isArray(historique) && historique.length > 0 ? `
                            <table style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Produit</th>
                                        <th>Ancien taux</th>
                                        <th>Nouveau taux</th>
                                        <th>Admin</th>
                                        <th>Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${historique.map(h => `
                                        <tr>
                                            <td>${new Date(h.date_modification).toLocaleDateString('fr-FR')}<br>
                                                <small>${new Date(h.date_modification).toLocaleTimeString('fr-FR')}</small>
                                            </td>
                                            <td><strong>${h.produit_nom}</strong></td>
                                            <td>${parseFloat(h.ancien_taux).toFixed(2)}%</td>
                                            <td style="color: ${h.nouveau_taux > h.ancien_taux ? '#4caf50' : '#f44336'};">
                                                ${parseFloat(h.nouveau_taux).toFixed(2)}%
                                            </td>
                                            <td>${h.admin_nom} ${h.admin_prenom}</td>
                                            <td><small>${h.commentaire || '-'}</small></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : '<p style="text-align: center; color: #666; margin: 2rem 0;">Aucun historique disponible</p>'}
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
            } catch (error) {
                console.error('Erreur lors du chargement de l\'historique:', error);
                showAlert('Erreur lors du chargement de l\'historique', 'error');
            }
        }

        // Fonction pour éditer un produit
        async function editProduct(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Modifier le produit</h3>
                    
                    <form id="edit-product-form">
                        <div class="form-group">
                            <label for="edit-nom">Nom du produit *</label>
                            <input type="text" id="edit-nom" name="nom" required 
                                   value="${product.nom}" placeholder="Ex: Burger Classic">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-type">Type *</label>
                            <select id="edit-type" name="type" required onchange="updateUniteOptions()">
                                <option value="aliment" ${product.type === 'aliment' ? 'selected' : ''}>Aliment</option>
                                <option value="boisson" ${product.type === 'boisson' ? 'selected' : ''}>Boisson</option>
                                <option value="accompagnement" ${product.type === 'accompagnement' ? 'selected' : ''}>Accompagnement</option>
                                <option value="dessert" ${product.type === 'dessert' ? 'selected' : ''}>Dessert</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-prix">Prix unitaire (€) *</label>
                            <input type="number" id="edit-prix" name="prix_unitaire" step="0.01" min="0" required
                                   value="${product.prix_unitaire}" placeholder="Ex: 8.50">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="edit-obligatoire" name="obligatoire" 
                                       ${product.obligatoire ? 'checked' : ''} onchange="toggleQuantiteMinimale()">
                                Produit obligatoire (partenaire)
                            </label>
                            <small style="color: #666;">Les produits obligatoires doivent être disponibles dans tous les camions</small>
                        </div>
                        
                        <div class="form-group" id="quantite-minimale-container" ${!product.obligatoire ? 'style="display: none;"' : ''}>
                            <label for="edit-quantite-minimale">Quantité minimale imposée</label>
                            <input type="number" id="edit-quantite-minimale" name="quantite_minimale" 
                                   min="0" step="1" value="${product.quantite_minimale || 0}"
                                   placeholder="Ex: 10">
                            <small style="color: #666;">Quantité minimale que chaque camion doit avoir en stock</small>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Modifier le produit</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('edit-product-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.id = productId;
                data.obligatoire = document.getElementById('edit-obligatoire').checked ? 1 : 0;
                
                await updateProduct(data);
            };
        }

        // Fonction pour mettre à jour un produit
        async function updateProduct(data) {
            try {
                // Si le produit n'est plus obligatoire, forcer quantite_minimale à 0
                if (data.obligatoire == 0) {
                    data.quantite_minimale = 0;
                }
                
                const response = await fetch('../api/produits/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit modifié avec succès', 'success');
                    closeModal();
                    await loadAllData();
                } else {
                    showAlert('Erreur : ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                showAlert('Erreur réseau lors de la modification', 'error');
            }
        }

        // Fonction pour supprimer un produit
        async function deleteProduct(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;
            
            if (!confirm(`Êtes-vous sûr de vouloir supprimer le produit "${product.nom}" ?\n\nCette action est irréversible et supprimera aussi tous les stocks associés.`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/produits/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: productId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit supprimé avec succès', 'success');
                    await loadAllData();
                } else {
                    showAlert('Erreur : ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                showAlert('Erreur réseau lors de la suppression', 'error');
            }
        }

        // Fonction pour ajouter un nouveau produit
        function showAddProductModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter un nouveau produit</h3>
                    
                    <form id="add-product-form">
                        <div class="form-group">
                            <label for="add-nom">Nom du produit *</label>
                            <input type="text" id="add-nom" name="nom" required 
                                   placeholder="Ex: Burger Classic">
                        </div>
                        
                        <div class="form-group">
                            <label for="add-type">Type *</label>
                            <select id="add-type" name="type" required onchange="updateUniteOptions()">
                                <option value="">Sélectionner un type</option>
                                <option value="aliment">Aliment</option>
                                <option value="boisson">Boisson</option>
                                <option value="accompagnement">Accompagnement</option>
                                <option value="dessert">Dessert</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-prix">Prix unitaire (€) *</label>
                            <input type="number" id="add-prix" name="prix_unitaire" step="0.01" min="0" required
                                   placeholder="Ex: 8.50">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="add-obligatoire" name="obligatoire" onchange="toggleQuantiteMinimale()">
                                Produit obligatoire (partenaire)
                            </label>
                            <small style="color: #666;">Les produits obligatoires doivent être disponibles dans tous les camions</small>
                        </div>
                        
                        <div class="form-group" id="quantite-minimale-container" style="display: none;">
                            <label for="add-quantite-minimale">Quantité minimale imposée</label>
                            <input type="number" id="add-quantite-minimale" name="quantite_minimale" 
                                   min="0" step="1" value="0" placeholder="Ex: 10">
                            <small style="color: #666;">Quantité minimale que chaque camion doit avoir en stock</small>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Ajouter le produit</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('add-product-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.obligatoire = document.getElementById('add-obligatoire').checked ? 1 : 0;
                
                await addProduct(data);
            };
        }

        // Fonction pour ajouter un produit
        async function addProduct(data) {
            try {
                // Si le produit n'est pas obligatoire, forcer quantite_minimale à 0
                if (data.obligatoire == 0) {
                    data.quantite_minimale = 0;
                }
                
                const response = await fetch('../api/produits/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit ajouté avec succès', 'success');
                    closeModal();
                    await loadAllData();
                } else {
                    showAlert('Erreur : ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de l\'ajout:', error);
                showAlert('Erreur réseau lors de l\'ajout', 'error');
            }
        }
    </script>
</body>
</html>