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
            
            <button class="add-btn" onclick="ProductManager.showAddModal()">+ Ajouter un produit</button>
            
            <div id="products-table-container"></div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script src="../js/admin/stock-management.js"></script>
    <script>
        // GESTIONNAIRE PRINCIPAL UNIFIÉ - DRY
        const ProductManager = {
            // ===== DONNÉES =====
            data: {
                products: [],
                stocks: []
            },

            // ===== CONFIGURATION CENTRALISÉE =====
            config: {
                endpoints: {
                    add: '../api/produits/add.php',
                    update: '../api/produits/update.php',
                    delete: '../api/produits/delete.php'
                },

                headers: ['ID', 'Nom du produit', 'Type', 'Prix unitaire', 'Obligatoire', 'Réduction fidélité', 'État stock', 'Actions'],

                formFields: {
                    add: [
                        { name: 'nom', label: 'Nom du produit *', type: 'text', required: true, 
                          placeholder: 'Ex: Coca-Cola, Pain, Salade...' },
                        { name: 'type', label: 'Type *', type: 'select', required: true, 
                          options: [
                              { value: '', text: 'Sélectionner un type' },
                              { value: 'aliment', text: 'Aliment' },
                              { value: 'boisson', text: 'Boisson' },
                              { value: 'préparé', text: 'Plat préparé' }
                          ]
                        },
                        { name: 'prix_unitaire', label: 'Prix unitaire (€) *', type: 'number', required: true, 
                          step: '0.01', min: '0', placeholder: 'Ex: 2.50' },
                        { name: 'obligatoire', label: 'Produit partenaire', type: 'checkbox', 
                          help: 'Les produits partenaires offrent des réductions aux clients' },
                        { name: 'quantite_minimale', label: 'Quantité minimale de commande', type: 'number', 
                          min: '0', step: '1', placeholder: '0 = pas de minimum',
                          help: 'Quantité minimale que les franchisés doivent commander' },
                        { name: 'reduction_fidelite', label: 'Réduction fidélité (%)', type: 'number', 
                          min: '0', max: '100', step: '0.1', placeholder: 'Ex: 5.0',
                          help: 'Réduction accordée aux clients fidèles' }
                    ],
                    edit: [
                        { name: 'nom', label: 'Nom du produit *', type: 'text', required: true },
                        { name: 'type', label: 'Type *', type: 'select', required: true, 
                          options: [
                              { value: 'aliment', text: 'Aliment' },
                              { value: 'boisson', text: 'Boisson' },
                              { value: 'préparé', text: 'Plat préparé' }
                          ]
                        },
                        { name: 'prix_unitaire', label: 'Prix unitaire (€) *', type: 'number', required: true, 
                          step: '0.01', min: '0' },
                        { name: 'obligatoire', label: 'Produit partenaire', type: 'checkbox' },
                        { name: 'quantite_minimale', label: 'Quantité minimale de commande', type: 'number', 
                          min: '0', step: '1' },
                        { name: 'reduction_fidelite', label: 'Réduction fidélité (%)', type: 'number', 
                          min: '0', max: '100', step: '0.1' }
                    ]
                }
            },

            // ===== INITIALISATION =====
            async init() {
                await this.loadData();
                this.display();
                this.updateGlobalStats();
            },

            // ===== CHARGEMENT DONNÉES =====
            async loadData() {
                await loadAllStockData();
                this.syncData();
            },

            syncData() {
                this.data.products = Array.isArray(globalStockData.products) ? globalStockData.products : [];
                this.data.stocks = Array.isArray(globalStockData.stocks) ? globalStockData.stocks : [];
            },

            // ===== AFFICHAGE =====
            display() {
                const container = document.getElementById('products-table-container');
                
                if (!this.data.products || this.data.products.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Aucun produit trouvé</p>';
                    return;
                }

                const table = document.createElement('table');
                table.innerHTML = `
                    <thead>
                        <tr>${this.config.headers.map(h => `<th>${h}</th>`).join('')}</tr>
                    </thead>
                    <tbody>
                        ${this.data.products.map(product => {
                            const cells = this.buildProductRow(product);
                            return `<tr>${cells.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
                        }).join('')}
                    </tbody>
                `;

                container.innerHTML = '';
                container.appendChild(table);
            },

            buildProductRow(product) {
                const productStocks = this.data.stocks.filter(s => s.produit_id == product.id);
                const stockStatusBadge = typeof getStockStatusBadge === 'function' ? 
                    getStockStatusBadge(productStocks) : 
                    '<span class="badge badge-secondary">N/A</span>';
                
                const obligatoireBadge = product.obligatoire ? 
                    '<span class="badge badge-success">Partenaire</span>' : 
                    '<span class="badge badge-secondary">Standard</span>';
                
                const reductionText = product.reduction_fidelite > 0 ? 
                    `${product.reduction_fidelite}%` : 
                    'Aucune';

                const prix = typeof AdminCommon?.utils?.formatPrice === 'function' ? 
                    AdminCommon.utils.formatPrice(product.prix_unitaire) :
                    `${parseFloat(product.prix_unitaire).toFixed(2)}€`;

                return [
                    product.id,
                    `<strong>${product.nom}</strong>${product.quantite_minimale > 0 ? `<br><small style="color: #666;">Quantité min: ${product.quantite_minimale}</small>` : ''}`,
                    `<span class="type-badge type-${product.type}">${product.type}</span>`,
                    `<strong>${prix}</strong>`,
                    obligatoireBadge,
                    reductionText,
                    `<div onclick="ProductManager.viewStocks(${product.id})" style="cursor: pointer;">${stockStatusBadge}</div>`,
                    `<button class="btn-action" onclick="ProductManager.edit(${product.id})">Modifier</button>
                     <button class="btn-action" onclick="ProductManager.viewStocks(${product.id})" title="Voir les stocks">📊</button>
                     <button class="btn-action danger" onclick="ProductManager.delete(${product.id})">Supprimer</button>`
                ];
            },

            // ===== MODALS MANUELLES =====
            showAddModal() {
                this.createProductModal('Ajouter un nouveau produit', null, this.handleAdd.bind(this));
            },

            edit(id) {
                const product = this.data.products.find(p => p.id == id);
                if (!product) {
                    alert('Produit non trouvé');
                    return;
                }
                this.createProductModal(`Modifier le produit #${id}`, product, this.handleEdit.bind(this, id));
            },

            createProductModal(title, data, onSubmit) {
                const isEdit = !!data;
                const fields = isEdit ? this.config.formFields.edit : this.config.formFields.add;
                
                const modal = document.createElement('div');
                modal.className = 'modal';
                
                const fieldsHTML = fields.map(field => this.buildFormField(field, data)).join('');

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${title}</h3>
                        <form id="product-modal-form">
                            ${fieldsHTML}
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="ProductManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('product-modal-form').addEventListener('submit', onSubmit);
            },

            buildFormField(field, data) {
                const value = data ? (data[field.name] || '') : '';
                const id = `product-${field.name}`;
                
                if (field.type === 'select') {
                    const optionsHTML = field.options.map(opt => 
                        `<option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>${opt.text}</option>`
                    ).join('');
                    
                    return `
                        <div class="form-group">
                            <label for="${id}">${field.label}</label>
                            <select id="${id}" name="${field.name}" ${field.required ? 'required' : ''}>
                                ${optionsHTML}
                            </select>
                            ${field.help ? `<small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.25rem;">${field.help}</small>` : ''}
                        </div>
                    `;
                } else if (field.type === 'checkbox') {
                    return `
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="${id}" name="${field.name}" ${value ? 'checked' : ''}>
                                ${field.label}
                            </label>
                            ${field.help ? `<small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.25rem;">${field.help}</small>` : ''}
                        </div>
                    `;
                } else {
                    const attributes = [];
                    if (field.required) attributes.push('required');
                    if (field.step) attributes.push(`step="${field.step}"`);
                    if (field.min) attributes.push(`min="${field.min}"`);
                    if (field.max) attributes.push(`max="${field.max}"`);
                    if (field.placeholder) attributes.push(`placeholder="${field.placeholder}"`);
                    
                    return `
                        <div class="form-group">
                            <label for="${id}">${field.label}</label>
                            <input type="${field.type}" id="${id}" name="${field.name}" value="${value}" ${attributes.join(' ')}>
                            ${field.help ? `<small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.25rem;">${field.help}</small>` : ''}
                        </div>
                    `;
                }
            },

            // ===== HANDLERS =====
            async handleAdd(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                const processedData = this.processFormData(data);
                
                if (!this.validateProductData(processedData)) return;

                try {
                    const response = await fetch(this.config.endpoints.add, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(processedData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Produit ajouté avec succès !');
                        this.closeModal();
                        await this.refreshData();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            async handleEdit(id, e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                const processedData = this.processFormData(data);
                processedData.id = id;
                
                if (!this.validateProductData(processedData)) return;

                try {
                    const response = await fetch(this.config.endpoints.update, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(processedData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Produit modifié avec succès !');
                        this.closeModal();
                        await this.refreshData();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            async delete(id) {
                const product = this.data.products.find(p => p.id == id);
                if (!product) return;
                
                if (!confirm(`Supprimer le produit "${product.nom}" ?\nCette action supprimera également tous les stocks associés.`)) return;

                try {
                    const response = await fetch(this.config.endpoints.delete, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Produit supprimé avec succès !');
                        await this.refreshData();
                    } else {
                        alert('❌ ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            // ===== FONCTIONS SPÉCIALISÉES =====
            viewStocks(productId) {
                if (typeof viewProductStocks === 'function') {
                    viewProductStocks(productId);
                } else {
                    console.warn('viewProductStocks function not available');
                    alert('Fonction de visualisation des stocks non disponible');
                }
            },

            // ===== UTILITAIRES =====
            processFormData(data) {
                return {
                    ...data,
                    obligatoire: data.obligatoire ? 1 : 0,
                    quantite_minimale: parseInt(data.quantite_minimale) || 0,
                    reduction_fidelite: parseFloat(data.reduction_fidelite) || 0
                };
            },

            validateProductData(data) {
                if (!data.nom || !data.type || !data.prix_unitaire) {
                    alert('❌ Veuillez remplir tous les champs obligatoires');
                    return false;
                }
                
                if (parseFloat(data.prix_unitaire) <= 0) {
                    alert('❌ Le prix unitaire doit être supérieur à 0');
                    return false;
                }
                
                return true;
            },

            updateGlobalStats() {
                if (typeof window.updateGlobalStats === 'function') {
                    window.updateGlobalStats();
                } else {
                    // Fallback manuel
                    const stats = {
                        ruptures: this.data.stocks.filter(s => s.quantite == 0).length,
                        alertes: this.data.stocks.filter(s => s.alerte == 1 && s.quantite > 0).length,
                        stocksOk: this.data.stocks.filter(s => s.alerte == 0 && s.quantite > 0).length,
                        totalEntrepots: globalStockData.entrepots ? globalStockData.entrepots.length : 0,
                        totalProduits: this.data.products.length
                    };

                    Object.entries(stats).forEach(([key, value]) => {
                        const elementId = `global-${key.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
                        const element = document.getElementById(elementId);
                        if (element) element.textContent = value;
                    });
                }
            },

            closeModal() {
                const modal = document.querySelector('.modal');
                if (modal) modal.remove();
            },

            async refreshData() {
                await this.loadData();
                this.display();
                this.updateGlobalStats();
            }
        };

        // ===== INITIALISATION AUTOMATIQUE =====
        document.addEventListener('DOMContentLoaded', function() {
            ProductManager.init();
        });

        // ===== FONCTIONS GLOBALES LEGACY (COMPATIBILITÉ) =====
        function displayProducts() { ProductManager.display(); }
        function showAddProductModal() { ProductManager.showAddModal(); }
        function editProduct(id) { ProductManager.edit(id); }
        function deleteProduct(id) { ProductManager.delete(id); }
        function buildProductRow(product) { return ProductManager.buildProductRow(product); }
    </script>
</body>
</html>