<?php
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
<body>
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
                <button onclick="showGlobalStockMatrix()" class="btn-nav">Vue globale</button>
                <button onclick="showStockAlerts()" class="btn-nav">Alertes</button>
                <button onclick="geocodeAllEntrepots()" class="btn-nav">Géolocaliser tous</button>
            </div>
            
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
            
            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('entrepots', displayEntrepots)">Entrepôts</button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('stocks', loadStocksTab)">Gestion des stocks</button>
            </div>

            <div id="tab-entrepots" class="tab-content active">
                <button class="add-btn" onclick="showAddEntrepotModal()">+ Ajouter un entrepôt</button>
                <div id="entrepots-table-container"></div>
            </div>

            <div id="tab-stocks" class="tab-content">
                <div style="margin-bottom: 1rem;">
                    <label for="filter-entrepot">Filtrer par entrepôt :</label>
                    <select id="filter-entrepot" onchange="loadStocksFiltered()" style="padding: 0.5rem; margin-left: 1rem;">
                        <option value="">Tous les entrepôts</option>
                    </select>
                    <button class="add-btn" onclick="showAddStockModal()" style="margin-left: 1rem;">+ Ajouter/Modifier stock</button>
                </div>
                <div id="stocks-table-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script src="../js/admin/stock-management.js"></script>

    <script>
        const entrepotManager = {
            data: { entrepots: [], stocks: [], produits: [] },
            
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
                        headers: ['ID', 'Nom', 'Adresse', 'Ville', 'Responsable', 'Nb produits', 'Alertes stock', 'Géolocalisation', 'Actions'],
                        rowBuilder: (entrepot) => {
                            const entrepotStocks = entrepotManager.data.stocks.filter(s => s.entrepot_id == entrepot.id);
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
                                `<button class="btn-action" onclick="editEntrepot(${entrepot.id})">Modifier</button>
                                 <button class="btn-action" onclick="viewEntrepotStocks(${entrepot.id})">Stocks</button>
                                 <button class="btn-action danger" onclick="deleteEntrepot(${entrepot.id})">Supprimer</button>`
                            ];
                        }
                    },
                    
                    stocks: {
                        headers: ['Entrepôt', 'Produit', 'Type', 'Quantité', 'Unité', 'Seuil d\'alerte', 'État', 'Dernière MAJ', 'Actions'],
                        rowBuilder: (stock) => {
                            const etat = stock.quantite == 0 ? 'Rupture' :
                                       stock.alerte == 1 ? 'Alerte' : 'OK';
                            const classeEtat = stock.quantite == 0 ? 'badge-danger' :
                                             stock.alerte == 1 ? 'badge-warning' : 'badge-success';
                            
                            return [
                                `<strong>${getEntrepotName(stock.entrepot_id)}</strong>`,
                                getProduitName(stock.produit_id),
                                `<span class="type-badge type-${stock.produit_type}">${stock.produit_type}</span>`,
                                `<strong>${parseFloat(stock.quantite).toFixed(2)}</strong>`,
                                stock.unite,
                                parseFloat(stock.seuil_alerte).toFixed(2),
                                `<span class="${classeEtat}" style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">${etat}</span>`,
                                `${AdminCommon.utils.formatDate(stock.derniere_maj)} ${new Date(stock.derniere_maj).toLocaleTimeString('fr-FR')}`,
                                `<button class="btn-action" onclick="editStock(${stock.entrepot_id}, ${stock.produit_id})">Modifier</button>
                                 <button class="btn-action danger" onclick="deleteStock(${stock.entrepot_id}, ${stock.produit_id})">Supprimer</button>`
                            ];
                        }
                    }
                },
                
                formFields: {
                    entrepot: [
                        { name: 'nom', label: 'Nom de l\'entrepôt', type: 'text', required: true },
                        { name: 'adresse', label: 'Adresse complète', type: 'text', required: true, attributes: 'placeholder="123 Rue de la Logistique"' },
                        { name: 'ville', label: 'Ville', type: 'text', required: true },
                        { name: 'code_postal', label: 'Code postal', type: 'text', required: true, attributes: 'pattern="[0-9]{5}" title="Code postal français (5 chiffres)"' },
                        { name: 'telephone', label: 'Téléphone', type: 'tel', attributes: 'placeholder="01 23 45 67 89"' },
                        { name: 'email', label: 'Email', type: 'email', attributes: 'placeholder="entrepot@drivncook.com"' },
                        { name: 'responsable', label: 'Responsable', type: 'text', attributes: 'placeholder="Jean Dupont"' }
                    ],
                    
                    stock: [
                        { name: 'entrepot_id', label: 'Entrepôt', type: 'select', required: true, options: 'getEntrepotOptions' },
                        { name: 'produit_id', label: 'Produit', type: 'select', required: true, options: 'getProduitOptions' },
                        { name: 'quantite', label: 'Quantité', type: 'number', required: true, attributes: 'step="0.01" min="0" placeholder="0.00"' },
                        { name: 'unite', label: 'Unité', type: 'select', required: true, options: [
                            { value: 'kg', text: 'Kilogrammes' },
                            { value: 'litres', text: 'Litres' },
                            { value: 'unites', text: 'Unités' }
                        ]},
                        { name: 'seuil_alerte', label: 'Seuil d\'alerte', type: 'number', attributes: 'step="0.01" min="0" placeholder="5.00"' }
                    ]
                }
            }
        };

        
        function getEntrepotName(entrepotId) {
            const entrepot = entrepotManager.data.entrepots.find(e => e.id == entrepotId);
            return entrepot ? entrepot.nom : 'Entrepôt inconnu';
        }

        function getProduitName(produitId) {
            const produit = entrepotManager.data.produits.find(p => p.id == produitId);
            return produit ? produit.nom : 'Produit inconnu';
        }

        function loadStocksTab() {
            displayStocks();
        }

        function editEntrepot(id) {
            const entrepot = entrepotManager.data.entrepots.find(e => e.id == id);
            if (!entrepot) {
                AdminCommon.utils.showAlert('Entrepôt non trouvé', 'error');
                return;
            }

            AdminCommon.utils.createFormModal({
                title: `Modifier l'entrepôt #${id}`,
                data: entrepot,
                fields: entrepotManager.config.formFields.entrepot,
                onSubmit: async (data, isEdit) => {
                    const success = await AdminCommon.utils.saveData(entrepotManager.config.endpoints.update, data, true);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadAllStockData();
                        syncData();
                        displayEntrepots();
                    }
                }
            });
        }

        function viewEntrepotStocks(entrepotId) {
            const entrepot = entrepotManager.data.entrepots.find(e => e.id == entrepotId);
            const entrepotStocks = entrepotManager.data.stocks.filter(s => s.entrepot_id == entrepotId);
            
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
                                    <td><strong>${getProduitName(stock.produit_id)}</strong></td>
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

            const actions = [
                { text: 'Fermer', type: 'secondary', onclick: 'AdminCommon.utils.closeModal()' },
                { text: 'Gérer les stocks', type: 'primary', onclick: `AdminCommon.utils.closeModal(); AdminCommon.utils.switchTab('stocks', loadStocksTab); document.getElementById('filter-entrepot').value='${entrepotId}'; loadStocksFiltered();` }
            ];

            AdminCommon.utils.createModal({
                title: `Stocks de l'entrepôt "${entrepot.nom}"`,
                content: stocksTableHTML,
                size: 'large',
                actions: actions
            });
        }

        async function deleteEntrepot(id) {
            const success = await AdminCommon.utils.deleteData(
                entrepotManager.config.endpoints.delete,
                { id: id },
                'Êtes-vous sûr de vouloir supprimer cet entrepôt ?\nTous les stocks associés seront également supprimés.'
            );
            
            if (success) {
                await loadAllStockData();
                syncData();
                displayEntrepots();
            }
        }

        function showAddEntrepotModal() {
            console.log('🔄 Ouverture modal ajout entrepôt...');
            
            AdminCommon.utils.createFormModal({
                title: 'Ajouter un entrepôt',
                fields: entrepotManager.config.formFields.entrepot,
                onSubmit: async (data, isEdit) => {
                    console.log('📤 Soumission du formulaire:', data);
                    
                    // Validation simple
                    if (!data.nom || !data.adresse || !data.ville || !data.code_postal) {
                        AdminCommon.utils.showAlert('❌ Veuillez remplir tous les champs obligatoires', 'error');
                        return;
                    }
                    
                    try {
                        console.log('📡 Envoi vers API...');
                        
                        const success = await AdminCommon.utils.saveData(entrepotManager.config.endpoints.add, data);
                        if (success) {
                            AdminCommon.utils.closeModal();
                            await loadAllStockData();
                            syncData();
                            displayEntrepots();
                        }
                        
                    } catch (error) {
                        console.error('❌ Erreur:', error);
                        AdminCommon.utils.showAlert('❌ Erreur lors de l\'ajout de l\'entrepôt', 'error');
                    }
                }
            });
        }

        function showAddStockModal() {
            AdminCommon.utils.createFormModal({
                title: 'Ajouter/Modifier un stock',
                fields: entrepotManager.config.formFields.stock.map(field => {
                    if (field.options === 'getEntrepotOptions') {
                        field.options = getEntrepotOptions();
                    } else if (field.options === 'getProduitOptions') {
                        field.options = getProduitOptions();
                    }
                    return field;
                }),
                onSubmit: async (data, isEdit) => {
                    const success = await AdminCommon.utils.saveData(entrepotManager.config.endpoints.stockAdd, data);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadAllStockData();
                        syncData();
                        displayStocks();
                    }
                }
            });
        }

        async function editStock(entrepotId, produitId) {
            const stock = entrepotManager.data.stocks.find(s => 
                s.entrepot_id == entrepotId && s.produit_id == produitId
            );
            
            if (!stock) {
                AdminCommon.utils.showAlert('Stock non trouvé', 'error');
                return;
            }

            AdminCommon.utils.createFormModal({
                title: 'Modifier le stock',
                data: {
                    entrepot_id: entrepotId,
                    produit_id: produitId,
                    quantite: stock.quantite,
                    unite: stock.unite,
                    seuil_alerte: stock.seuil_alerte
                },
                fields: [
                    { name: 'entrepot_id', type: 'hidden' },
                    { name: 'produit_id', type: 'hidden' },
                    { name: 'quantite', label: 'Quantité', type: 'number', required: true, attributes: 'step="0.01" min="0"' },
                    { name: 'unite', label: 'Unité', type: 'select', required: true, options: [
                        { value: 'kg', text: 'Kilogrammes' },
                        { value: 'litres', text: 'Litres' },
                        { value: 'unites', text: 'Unités' }
                    ]},
                    { name: 'seuil_alerte', label: 'Seuil d\'alerte', type: 'number', attributes: 'step="0.01" min="0"' }
                ],
                onSubmit: async (data, isEdit) => {
                    const success = await AdminCommon.utils.saveData(entrepotManager.config.endpoints.stockUpdate, data, true);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadAllStockData();  
                        syncData();                
                        displayStocks();           
                    }
                }
            });
        }

        async function deleteStock(entrepotId, produitId) {
            const entrepotNom = getEntrepotName(entrepotId);
            const produitNom = getProduitName(produitId);
            
            const success = await AdminCommon.utils.deleteData(
                entrepotManager.config.endpoints.stockDelete,
                { entrepot_id: entrepotId, produit_id: produitId },
                `Êtes-vous sûr de vouloir supprimer le stock de "${produitNom}" dans l'entrepôt "${entrepotNom}" ?`
            );
            
            if (success) {
                await loadAllStockData();
                syncData();
                displayStocks();
            }
        }

        async function loadStocksFiltered() {
            const entrepotId = document.getElementById('filter-entrepot').value;
            
            try {
                const url = entrepotId ? 
                    `../api/stocks/list.php?entrepot_id=${entrepotId}` :
                    '../api/stocks/list.php';
                const stocks = await AdminCommon.utils.apiRequest(url);
                
                entrepotManager.data.stocks = stocks;
                displayStocks();
                updateGlobalStats();
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des stocks', 'error');
            }
        }

        function getEntrepotOptions() {
            return [
                { value: '', text: 'Sélectionner un entrepôt' },
                ...entrepotManager.data.entrepots.map(e => ({ value: e.id, text: e.nom }))
            ];
        }

        function getProduitOptions() {
            return [
                { value: '', text: 'Sélectionner un produit' },
                ...entrepotManager.data.produits.map(p => ({ value: p.id, text: `${p.nom} (${p.type})` }))
            ];
        }

        document.addEventListener('DOMContentLoaded', async function() {
            console.log('🚀 Initialisation de la page entrepôts...');
            
            try {
                await loadAllStockData();
                syncData();
                displayEntrepots();
                populateEntrepotFilter();
                startGlobalMonitoring();
                console.log('✅ Initialisation terminée');
            } catch (error) {
                console.error('❌ Erreur lors de l\'initialisation:', error);
                AdminCommon.utils.showAlert('Erreur lors de l\'initialisation de la page', 'error');
            }
        });
    </script>
</body>
</html>