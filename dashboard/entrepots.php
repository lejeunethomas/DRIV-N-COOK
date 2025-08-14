<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des entrepôts - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stock-alerte { color: #f44336; font-weight: bold; }
        .stock-ok { color: #4caf50; }
        #geocoding-status { margin: 1rem 0; }
        #coordinates-section { display: none; }
        .geocoding-info { font-size: 0.9rem; color: #666; margin-top: 0.5rem; }
    </style>
</head>
<body data-role="admin">
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php" class="active">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content admin">
            <h1 class="admin">Gestion des entrepôts et stocks</h1>
            
            <div class="quick-nav">
                <a href="produits.php" class="btn-nav">Produits</a>
                <a href="#" class="btn-nav current">Entrepôts</a>
                <button onclick="EntrepotManager.showGlobalMatrix()" class="btn-nav">Vue globale</button>
                <button onclick="EntrepotManager.showAlerts()" class="btn-nav">Alertes</button>
                <button onclick="EntrepotManager.geocodeAll()" class="btn-nav">Géolocaliser tous</button>
            </div>

            <div id="geocoding-status" style="margin:0.75rem 0;font-size:0.85rem;color:#555;"></div>
            
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
            
            <!-- Onglets -->
            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('entrepots', EntrepotManager.displayEntrepots)">Entrepôts</button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('stocks', EntrepotManager.displayStocks)">Gestion des stocks</button>
            </div>

            <!-- Contenu onglet Entrepôts -->
            <div id="tab-entrepots" class="tab-content active">
                <button class="add-btn" onclick="EntrepotManager.showAddModal()">+ Ajouter un entrepôt</button>
                <div id="entrepots-table-container"></div>
            </div>

            <!-- Contenu onglet Stocks -->
            <div id="tab-stocks" class="tab-content">
                <div style="margin-bottom: 1rem;">
                    <label for="filter-entrepot">Filtrer par entrepôt :</label>
                    <select id="filter-entrepot" onchange="EntrepotManager.filterStocks()" style="padding: 0.5rem; margin-left: 1rem;">
                        <option value="">Tous les entrepôts</option>
                    </select>
                    <button class="add-btn" onclick="EntrepotManager.showAddStockModal()" style="margin-left: 1rem;">+ Ajouter/Modifier stock</button>
                </div>
                <div id="stocks-table-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script src="../js/admin/geolocation.js"></script>
    <script src="../js/admin/stock-management.js"></script>
    <script>
        // GESTIONNAIRE PRINCIPAL
        const EntrepotManager = {
            // ===== DONNÉES =====
            data: {
                entrepots: [],
                stocks: [],
                produits: []
            },

            // ===== CONFIGURATION CENTRALISÉE =====
            config: {
                endpoints: {
                    entrepots: '../api/entrepots/list.php',
                    add: '../api/entrepots/add.php',
                    update: '../api/entrepots/update.php',
                    delete: '../api/entrepots/delete.php',
                    stockAdd: '../api/stocks/add.php',
                    stockUpdate: '../api/stocks/update.php',
                    stockDelete: '../api/stocks/delete.php'
                },

                tables: {
                    entrepots: {
                        headers: ['ID', 'Nom', 'Adresse', 'Ville', 'Responsable', 'Nb produits', 'Alertes stock', 'Géolocalisation', 'Actions']
                    },
                    stocks: {
                        headers: ['Entrepôt', 'Produit', 'Type', 'Quantité', 'Unité', 'Seuil d\'alerte', 'État', 'Dernière MAJ', 'Actions']
                    }
                },

                formFields: {
                    entrepot: [
                        { name: 'nom', label: 'Nom de l\'entrepôt *', type: 'text', required: true },
                        { name: 'adresse', label: 'Adresse complète *', type: 'text', required: true, placeholder: '123 Rue de la Logistique' },
                        { name: 'ville', label: 'Ville *', type: 'text', required: true },
                        { name: 'code_postal', label: 'Code postal *', type: 'text', required: true, pattern: '[0-9]{5}' },
                        { name: 'telephone', label: 'Téléphone', type: 'tel', placeholder: '01 23 45 67 89' },
                        { name: 'email', label: 'Email', type: 'email', placeholder: 'entrepot@drivncook.com' },
                        { name: 'responsable', label: 'Responsable', type: 'text', placeholder: 'Jean Dupont' }
                    ],
                    stock: [
                        { name: 'entrepot_id', label: 'Entrepôt *', type: 'select', required: true, options: 'getEntrepotOptions' },
                        { name: 'produit_id', label: 'Produit *', type: 'select', required: true, options: 'getProduitOptions' },
                        { name: 'quantite', label: 'Quantité *', type: 'number', required: true, step: '0.01', min: '0' },
                        { name: 'unite', label: 'Unité *', type: 'select', required: true, options: [
                            { value: 'kg', text: 'Kilogrammes' },
                            { value: 'litres', text: 'Litres' },
                            { value: 'unites', text: 'Unités' }
                        ]},
                        { name: 'seuil_alerte', label: 'Seuil d\'alerte', type: 'number', step: '0.01', min: '0' }
                    ]
                }
            },

            // ===== INITIALISATION =====
            async init() {
                await this.loadAllData();
                this.syncGlobalData();
                this.displayEntrepots();
                this.populateFilters();
                this.updateGlobalStats();
                this.initDiagnostics();
            },

            // ===== CHARGEMENT DONNÉES =====
            async loadAllData() {
                await loadAllStockData();
                this.syncGlobalData();
            },

            syncGlobalData() {
                this.data.entrepots = Array.isArray(globalStockData.entrepots) ? globalStockData.entrepots : [];
                this.data.stocks = Array.isArray(globalStockData.stocks) ? globalStockData.stocks : [];
                this.data.produits = Array.isArray(globalStockData.products) ? globalStockData.products : [];
            },

            // ===== UTILITAIRES =====
            getEntrepotName(id) {
                const entrepot = this.data.entrepots.find(e => e.id == id);
                return entrepot ? entrepot.nom : 'Entrepôt inconnu';
            },

            getProduitName(id) {
                const produit = this.data.produits.find(p => p.id == id);
                return produit ? produit.nom : 'Produit inconnu';
            },

            getEntrepotOptions() {
                return [
                    { value: '', text: 'Sélectionner un entrepôt' },
                    ...this.data.entrepots.map(e => ({ value: e.id, text: e.nom }))
                ];
            },

            getProduitOptions() {
                return [
                    { value: '', text: 'Sélectionner un produit' },
                    ...this.data.produits.map(p => ({ value: p.id, text: `${p.nom} (${p.type})` }))
                ];
            },

            // ===== CONSTRUCTEURS DE LIGNES TABLEAUX =====
            buildEntrepotRow(entrepot) {
                const entrepotStocks = EntrepotManager.data.stocks.filter(s => s.entrepot_id == entrepot.id);
                const nbProduits = entrepotStocks.length;
                const alertes = entrepotStocks.filter(s => s.quantite == 0 || s.alerte == 1).length;
                
                const coordonnees = entrepot.latitude && entrepot.longitude ? 
                    `<span style="color: #4caf50;">📍 Géolocalisé</span>` : 
                    `<span style="color: #f44336;">❌ Non géolocalisé</span>`;
                
                const alertesBadge = alertes > 0 ? 
                    `<span class="badge-danger">${alertes} alerte(s)</span>` :
                    `<span class="badge-success">Aucune alerte</span>`;
                
                return [
                    entrepot.id,
                    `<strong>${entrepot.nom}</strong><br><small style="color: #666;">${entrepot.responsable || 'Aucun responsable'}</small>`,
                    entrepot.adresse,
                    `${entrepot.ville}<br><small style="color: #666;">${entrepot.code_postal}</small>`,
                    entrepot.responsable || '-',
                    `${nbProduits} produit(s)`,
                    alertesBadge,
                    coordonnees,
                    `<button class="btn-action" onclick="EntrepotManager.edit(${entrepot.id})">Modifier</button>
                     <button class="btn-action" onclick="EntrepotManager.viewStocks(${entrepot.id})">Stocks</button>
                     <button class="btn-action danger" onclick="EntrepotManager.delete(${entrepot.id})">Supprimer</button>`
                ];
            },

            buildStockRow(stock) {
                const etat = stock.quantite == 0 ? 'Rupture' :
                           stock.alerte == 1 ? 'Alerte' : 'OK';
                const classeEtat = stock.quantite == 0 ? 'badge-danger' :
                                 stock.alerte == 1 ? 'badge-warning' : 'badge-success';
                
                return [
                    `<strong>${EntrepotManager.getEntrepotName(stock.entrepot_id)}</strong>`,
                    EntrepotManager.getProduitName(stock.produit_id),
                    `<span class="type-badge type-${stock.produit_type}">${stock.produit_type}</span>`,
                    `<strong>${parseFloat(stock.quantite).toFixed(2)}</strong>`,
                    stock.unite,
                    parseFloat(stock.seuil_alerte).toFixed(2),
                    `<span class="${classeEtat}" style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">${etat}</span>`,
                    `${AdminCommon.utils.formatDate(stock.derniere_maj)} ${new Date(stock.derniere_maj).toLocaleTimeString('fr-FR')}`,
                    `<button class="btn-action" onclick="EntrepotManager.editStock(${stock.entrepot_id}, ${stock.produit_id})">Modifier</button>
                     <button class="btn-action danger" onclick="EntrepotManager.deleteStock(${stock.entrepot_id}, ${stock.produit_id})">Supprimer</button>`
                ];
            },

            // ===== AFFICHAGE =====
            displayEntrepots() {
                AdminCommon.utils.createTable({
                    containerId: 'entrepots-table-container',
                    headers: this.config.tables.entrepots.headers,
                    data: this.data.entrepots,
                    rowBuilder: (entrepot) => {
                        const row = document.createElement('tr');
                        const cells = this.buildEntrepotRow(entrepot);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucun entrepôt trouvé'
                });
            },

            displayStocks() {
                AdminCommon.utils.createTable({
                    containerId: 'stocks-table-container',
                    headers: this.config.tables.stocks.headers,
                    data: this.data.stocks,
                    rowBuilder: (stock) => {
                        const row = document.createElement('tr');
                        const cells = this.buildStockRow(stock);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucun stock trouvé'
                });
            },

            populateFilters() {
                const filterSelect = document.getElementById('filter-entrepot');
                filterSelect.innerHTML = `<option value="">Tous les entrepôts</option>`;
                this.data.entrepots.forEach(e => {
                    filterSelect.innerHTML += `<option value="${e.id}">${e.nom}</option>`;
                });
            },

            updateGlobalStats() {
                this.updateLocalStats();
            },

            updateLocalStats() {
                if (!this.data.stocks || !this.data.entrepots || !this.data.produits) {
                    console.warn('⚠️ Données non encore chargées, tentative de rechargement...');
                    this.syncGlobalData();
                }

                const stats = {
                    ruptures: this.data.stocks.filter(s => s.quantite == 0).length,
                    alertes: this.data.stocks.filter(s => s.alerte == 1 && s.quantite > 0).length,
                    stocksOk: this.data.stocks.filter(s => s.alerte == 0 && s.quantite > 0).length,
                    totalEntrepots: this.data.entrepots.length,
                    totalProduits: this.data.produits.length
                };

                console.log('📊 Stats calculées:', stats);

                this.updateStatsDisplay(stats);

                if (window.globalStockData) {
                    window.globalStockData.globalStats = stats;
                }
            },

            updateStatsDisplay(stats) {
                const mapping = {
                    'global-ruptures': stats.ruptures,
                    'global-alertes': stats.alertes,
                    'global-stocks-ok': stats.stocksOk,
                    'global-entrepots': stats.totalEntrepots,
                    'global-produits': stats.totalProduits
                };

                Object.entries(mapping).forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = value;
                        console.log(`✅ ${id}: ${value}`);
                    }
                });
            },

            // ===== MODALS ENTREPÔTS =====
            showAddModal() {
                this.createEntrepotModal('Ajouter un entrepôt', null, this.handleAdd.bind(this));
            },

            async edit(id) {
                const entrepot = this.data.entrepots.find(e => e.id == id);
                if (!entrepot) {
                    alert('Entrepôt non trouvé');
                    return;
                }
                this.createEntrepotModal(`Modifier l'entrepôt #${id}`, entrepot, this.handleEdit.bind(this, id));
            },

            createEntrepotModal(title, data, onSubmit) {
                const isEdit = !!data;
                const modal = document.createElement('div');
                modal.className = 'modal';
                
                const fieldsHTML = this.config.formFields.entrepot.map(field => 
                    `<div class="form-group">
                        <label for="${isEdit ? 'edit-' : 'add-'}${field.name}">${field.label}</label>
                        <input type="${field.type}" 
                               id="${isEdit ? 'edit-' : 'add-'}${field.name}" 
                               name="${field.name}" 
                               value="${data ? (data[field.name] || '') : ''}"
                               ${field.required ? 'required' : ''}
                               ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}
                               ${field.pattern ? `pattern="${field.pattern}"` : ''}>
                    </div>`
                ).join('');

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${title}</h3>
                        <form id="entrepot-modal-form">
                            ${fieldsHTML}
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="EntrepotManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('entrepot-modal-form').addEventListener('submit', onSubmit);
            },

            // ===== HANDLERS ENTREPÔTS =====
            async handleAdd(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                
                if (!this.validateEntrepotData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.add, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Entrepôt ajouté avec succès !');
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
                data.id = id;
                
                if (!this.validateEntrepotData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.update, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Entrepôt modifié avec succès !');
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
                const entrepot = this.data.entrepots.find(e => e.id == id);
                if (!entrepot) return;
                
                if (!confirm(`Supprimer l'entrepôt "${entrepot.nom}" ?\n(S'il contient des stocks il sera désactivé)`)) return;

                try {
                    const response = await fetch(this.config.endpoints.delete, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ ' + result.message);
                        await this.refreshData();
                    } else {
                        alert('❌ ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            // ===== MODALS STOCKS =====
            showAddStockModal() {
                this.createStockModal('Ajouter/Modifier un stock', null, this.handleAddStock.bind(this));
            },

            async editStock(entrepotId, produitId) {
                const stock = this.data.stocks.find(s => s.entrepot_id == entrepotId && s.produit_id == produitId);
                if (!stock) {
                    alert('Stock non trouvé');
                    return;
                }
                this.createStockModal('Modifier le stock', stock, this.handleEditStock.bind(this, entrepotId, produitId));
            },

            createStockModal(title, data, onSubmit) {
                const isEdit = !!data;
                const modal = document.createElement('div');
                modal.className = 'modal';
                
                let infoHTML = '';
                if (isEdit) {
                    infoHTML = `
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong>Entrepôt :</strong> ${this.getEntrepotName(data.entrepot_id)}<br>
                            <strong>Produit :</strong> ${this.getProduitName(data.produit_id)}<br>
                            <strong>Stock actuel :</strong> ${parseFloat(data.quantite).toFixed(2)} ${data.unite}
                        </div>
                    `;
                }

                const fieldsHTML = this.config.formFields.stock.map(field => {
                    if (isEdit && (field.name === 'entrepot_id' || field.name === 'produit_id')) {
                        return `<input type="hidden" name="${field.name}" value="${data[field.name]}">`;
                    }
                    
                    if (field.type === 'select') {
                        const options = field.name === 'entrepot_id' ? this.getEntrepotOptions() : 
                                       field.name === 'produit_id' ? this.getProduitOptions() : 
                                       field.options;
                        
                        const optionsHTML = options.map(opt => 
                            `<option value="${opt.value}" ${data && data[field.name] == opt.value ? 'selected' : ''}>${opt.text}</option>`
                        ).join('');
                        
                        return `
                            <div class="form-group">
                                <label for="stock-${field.name}">${field.label}</label>
                                <select id="stock-${field.name}" name="${field.name}" ${field.required ? 'required' : ''}>
                                    ${optionsHTML}
                                </select>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="form-group">
                                <label for="stock-${field.name}">${field.label}</label>
                                <input type="${field.type}" 
                                       id="stock-${field.name}" 
                                       name="${field.name}" 
                                       value="${data ? (data[field.name] || '') : ''}"
                                       ${field.required ? 'required' : ''}
                                       ${field.step ? `step="${field.step}"` : ''}
                                       ${field.min ? `min="${field.min}"` : ''}>
                            </div>
                        `;
                    }
                }).join('');

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${title}</h3>
                        ${infoHTML}
                        <form id="stock-modal-form">
                            ${fieldsHTML}
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="EntrepotManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('stock-modal-form').addEventListener('submit', onSubmit);
            },

            // ===== HANDLERS STOCKS =====
            async handleAddStock(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                
                if (!this.validateStockData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.stockAdd, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Stock ajouté avec succès !');
                        this.closeModal();
                        await this.refreshData();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            async handleEditStock(entrepotId, produitId, e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                data.entrepot_id = entrepotId;
                data.produit_id = produitId;
                
                if (!this.validateStockData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.stockUpdate, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Stock modifié avec succès !');
                        this.closeModal();
                        await this.refreshData();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            async deleteStock(entrepotId, produitId) {
                const entrepotNom = this.getEntrepotName(entrepotId);
                const produitNom = this.getProduitName(produitId);
                
                if (!confirm(`Êtes-vous sûr de vouloir supprimer le stock de "${produitNom}" dans l'entrepôt "${entrepotNom}" ?`)) return;

                try {
                    const response = await fetch(this.config.endpoints.stockDelete, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entrepot_id: entrepotId, produit_id: produitId })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Stock supprimé avec succès !');
                        await this.refreshData();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            // ===== FONCTIONS SPÉCIALISÉES =====
            async filterStocks() {
                const entrepotId = document.getElementById('filter-entrepot').value;
                
                try {
                    const url = entrepotId ? 
                        `../api/stocks/list.php?entrepot_id=${entrepotId}` :
                        '../api/stocks/list.php';
                    const stocks = await AdminCommon.utils.apiRequest(url);
                    
                    this.data.stocks = stocks;
                    this.displayStocks();
                    this.updateGlobalStats();
                    
                } catch (error) {
                    AdminCommon.utils.showAlert('Erreur lors du chargement des stocks', 'error');
                }
            },

            viewStocks(entrepotId) {
                const entrepot = this.data.entrepots.find(e => e.id == entrepotId);
                const entrepotStocks = this.data.stocks.filter(s => s.entrepot_id == entrepotId);
                
                const stocksTableHTML = entrepotStocks.length === 0 ? 
                    '<p style="text-align: center; color: #f44336; font-weight: bold; margin: 2rem 0;">Aucun stock défini pour cet entrepôt</p>' :
                    `<table style="margin: 0;">
                        <thead>
                            <tr><th>Produit</th><th>Type</th><th>Quantité</th><th>Seuil</th><th>État</th><th>Dernière MAJ</th></tr>
                        </thead>
                        <tbody>
                            ${entrepotStocks.map(stock => {
                                const etat = stock.quantite == 0 ? 'Rupture' : stock.alerte == 1 ? 'Alerte' : 'OK';
                                const classeEtat = stock.quantite == 0 ? 'badge-danger' : stock.alerte == 1 ? 'badge-warning' : 'badge-success';
                                
                                return `
                                    <tr>
                                        <td><strong>${this.getProduitName(stock.produit_id)}</strong></td>
                                        <td><span class="type-badge type-${stock.produit_type}">${stock.produit_type}</span></td>
                                        <td><strong>${parseFloat(stock.quantite).toFixed(2)} ${stock.unite}</strong></td>
                                        <td>${parseFloat(stock.seuil_alerte).toFixed(2)} ${stock.unite}</td>
                                        <td><span class="${classeEtat}" style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem;">${etat}</span></td>
                                        <td>${AdminCommon.utils.formatDate(stock.derniere_maj)}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>`;

                AdminCommon.utils.createModal({
                    title: `Stocks de l'entrepôt "${entrepot.nom}"`,
                    content: stocksTableHTML,
                    size: 'large',
                    actions: [
                        { text: 'Fermer', type: 'secondary', onclick: 'AdminCommon.utils.closeModal()' },
                        { text: 'Gérer les stocks', type: 'primary', onclick: `AdminCommon.utils.closeModal(); AdminCommon.utils.switchTab('stocks', EntrepotManager.displayStocks); document.getElementById('filter-entrepot').value='${entrepotId}'; EntrepotManager.filterStocks();` }
                    ]
                });
            },

            // ===== INTÉGRATIONS STOCK-MANAGEMENT =====
            showGlobalMatrix() {
                this.runStockModule('showGlobalStockMatrix');
            },

            showAlerts() {
                this.runStockModule('showStockAlerts');
            },

            geocodeAll() {
                this.runStockModule('geocodeAllEntrepots');
            },

            runStockModule(methodName) {
                // Backup et synchronisation des données pour stock-management.js
                const backup = window.globalStockData;
                window.globalStockData = {
                    products: this.data.produits,
                    entrepots: this.data.entrepots,
                    stocks: this.data.stocks,
                    globalStats: backup?.globalStats || { ruptures: 0, alertes: 0, stocksOk: 0, totalEntrepots: 0, totalProduits: 0 }
                };
                
                try {
                    if (typeof window[methodName] === 'function') {
                        window[methodName]();
                    } else {
                        setTimeout(() => this.runStockModule(methodName), 100);
                    }
                } catch (e) {
                    console.error(`Erreur dans ${methodName}:`, e);
                    alert(`Erreur lors de l'exécution de ${methodName}`);
                } finally {
                    window.globalStockData = backup;
                }
            },

            // ===== VALIDATIONS =====
            validateEntrepotData(data) {
                if (!data.nom || !data.adresse || !data.ville || !data.code_postal) {
                    alert('❌ Veuillez remplir tous les champs obligatoires');
                    return false;
                }
                return true;
            },

            validateStockData(data) {
                if (!data.entrepot_id || !data.produit_id || !data.quantite || !data.unite) {
                    alert('❌ Veuillez remplir tous les champs obligatoires');
                    return false;
                }
                return true;
            },

            // ===== UTILITAIRES GÉNÉRAUX =====
            closeModal() {
                const modal = document.querySelector('.modal');
                if (modal) modal.remove();
            },

            async refreshData() {
                await this.loadAllData();
                this.displayEntrepots();
                this.displayStocks();
                this.populateFilters();
                this.updateGlobalStats();
            },

            initDiagnostics() {
                console.log('=== DIAGNOSTIC GEOLOCALISATION ===');
                console.log('geocodeAddress:', typeof window.geocodeAddress);
                console.log('geocodeAllEntrepots:', typeof window.geocodeAllEntrepots);
                console.log('Entrepôts sans coordonnées:', 
                    this.data.entrepots.filter(e => !e.latitude || !e.longitude).length
                );
                console.log('=== FIN DIAGNOSTIC ===');
            }
        };

        // ===== INITIALISATION AUTOMATIQUE =====
        document.addEventListener('DOMContentLoaded', function() {
            EntrepotManager.init();
        });


        function updateGlobalStats() {
            if (typeof window.globalStockData !== 'undefined' && window.globalStockData.stocks) {
                const stats = window.globalStockData.globalStats;
                const stocks = window.globalStockData.stocks;
                
                stats.ruptures = stocks.filter(s => s.quantite == 0).length;
                stats.alertes = stocks.filter(s => s.alerte == 1 && s.quantite > 0).length;
                stats.stocksOk = stocks.filter(s => s.alerte == 0 && s.quantite > 0).length;
                stats.totalEntrepots = window.globalStockData.entrepots.length;
                stats.totalProduits = window.globalStockData.products.length;
                
                ['global-ruptures', 'global-alertes', 'global-stocks-ok', 'global-entrepots', 'global-produits'].forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        const value = stats[id.replace('global-', '').replace('-', '')] || 
                                     stats[id.replace('global-', '')] || 0;
                        element.textContent = value;
                    }
                });
            }
        }
        
        function startGlobalMonitoring() { 
            console.log('Monitoring global démarré');
        }
    </script>
</body>
</html>