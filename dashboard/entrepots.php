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
            products: '../api/produits/list.php',
            stocks: '../api/stocks/list.php'
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

        function startGlobalMonitoring() {
            updateGlobalStats();
        }

        function updateGlobalStats() {
            try {
                const nbEntrepots = entrepotManager.data.entrepots.length;
                const nbProduits = entrepotManager.data.produits.length;
                const stocks = entrepotManager.data.stocks;
                
                const ruptures = stocks.filter(s => s.quantite == 0).length;
                const alertes = stocks.filter(s => s.quantite > 0 && s.alerte == 1).length;
                const stocksOk = stocks.filter(s => s.quantite > 0 && s.alerte != 1).length;
                
                if (document.getElementById('global-entrepots')) document.getElementById('global-entrepots').textContent = nbEntrepots;
                if (document.getElementById('global-produits')) document.getElementById('global-produits').textContent = nbProduits;
                if (document.getElementById('global-ruptures')) document.getElementById('global-ruptures').textContent = ruptures;
                if (document.getElementById('global-alertes')) document.getElementById('global-alertes').textContent = alertes;
                if (document.getElementById('global-stocks-ok')) document.getElementById('global-stocks-ok').textContent = stocksOk;
            } catch (error) {
                console.error('Erreur mise à jour stats:', error);
            }
        }

        function showGlobalStockMatrix() {
            // Utiliser la fonction de stock-management.js mais adapter les données
            const tempGlobalData = globalStockData;
            globalStockData.products = entrepotManager.data.produits;
            globalStockData.entrepots = entrepotManager.data.entrepots;
            globalStockData.stocks = entrepotManager.data.stocks;
            
            // Appeler la fonction de stock-management.js
            if (typeof window.showGlobalStockMatrix === 'function') {
                window.showGlobalStockMatrix();
            } else {
                alert('Matrice des stocks en développement');
            }
            
            globalStockData = tempGlobalData;
        }

        function showStockAlerts() {
            alert('Alertes de stock en développement');
        }

        function geocodeAllEntrepots() {
            alert('Géolocalisation automatique en développement');
        }

        document.addEventListener('DOMContentLoaded', async function() {
            await loadAllStockData();
            syncData();
            displayEntrepots();
            populateEntrepotFilter();
            startGlobalMonitoring();
        });

        function syncData() {
            entrepotManager.data.entrepots = Array.isArray(globalStockData.entrepots) ? globalStockData.entrepots : [];
            entrepotManager.data.stocks = Array.isArray(globalStockData.stocks) ? globalStockData.stocks : [];
            entrepotManager.data.produits = Array.isArray(globalStockData.products) ? globalStockData.products : [];
        }
    }
};

// ✅ FONCTIONS UTILITAIRES
function getEntrepotName(entrepotId) {
    const entrepot = entrepotManager.data.entrepots.find(e => e.id == entrepotId);
    return entrepot ? entrepot.nom : 'Entrepôt inconnu';
}

function getProduitName(produitId) {
    const produit = entrepotManager.data.produits.find(p => p.id == produitId);
    return produit ? produit.nom : 'Produit inconnu';
}

function getProduitType(produitId) {
    const produit = entrepotManager.data.produits.find(p => p.id == produitId);
    return produit ? produit.type : 'unknown';
}

function getEntrepotOptions() {
    return [
        { value: '', text: 'Sélectionner un entrepôt' },
        ...entrepotManager.data.entrepots.map(e => ({ value: e.id, text: e.nom }))
    ];
}

        function showAddEntrepotModal() {
            if (typeof AdminCommon?.utils?.createFormModal !== 'function') {
                alert('Erreur: Système AdminCommon non disponible');
                return;
            }
            
            try {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>Ajouter un entrepôt</h3>
                        <form id="entrepot-form">
                            <div class="form-group">
                                <label for="nom">Nom de l'entrepôt *</label>
                                <input type="text" id="nom" name="nom" required>
                            </div>
                            <div class="form-group">
                                <label for="adresse">Adresse complète *</label>
                                <input type="text" id="adresse" name="adresse" required placeholder="123 Rue de la Logistique">
                            </div>
                            <div class="form-group">
                                <label for="ville">Ville *</label>
                                <input type="text" id="ville" name="ville" required>
                            </div>
                            <div class="form-group">
                                <label for="code_postal">Code postal *</label>
                                <input type="text" id="code_postal" name="code_postal" required pattern="[0-9]{5}" title="Code postal français (5 chiffres)">
                            </div>
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" placeholder="01 23 45 67 89">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" placeholder="entrepot@drivncook.com">
                            </div>
                            <div class="form-group">
                                <label for="responsable">Responsable</label>
                                <input type="text" id="responsable" name="responsable" placeholder="Jean Dupont">
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeEntrepotModal()">Annuler</button>
                                <button type="submit" class="btn-primary">Ajouter</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);

                // Gérer la soumission du formulaire
                document.getElementById('entrepot-form').addEventListener('submit', async function(e) {
                    e.preventDefault();
    
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
    
                    if (!data.nom || !data.adresse || !data.ville || !data.code_postal) {
                        alert('❌ Veuillez remplir tous les champs obligatoires');
                        return;
                    }
    
                    try {
                        const response = await fetch('../api/entrepots/add.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(data)
                        });
    
                        const result = await response.json();
    
                        if (result.success) {
                            alert('✅ Entrepôt ajouté avec succès !');
                            closeEntrepotModal();
                            
                            await loadAllStockData();
                            syncData();
                            displayEntrepots();
                            populateEntrepotFilter();
                            updateGlobalStats();
                            
                        } else {
                            alert('❌ Erreur: ' + result.message);
                        }
    
                    } catch (error) {
                        alert('❌ Erreur réseau: ' + error.message);
                    }
                });

            } catch (error) {
                alert('Erreur lors de l\'ouverture du formulaire: ' + error.message);
            }
        }

        // ✅ Fonction pour fermer le modal manuel
        function closeEntrepotModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => modal.remove());
        }
        
        // Charger les produits
        try {
            const produitsResponse = await fetch(entrepotManager.config.endpoints.products);
            if (produitsResponse.ok) {
                globalStockData.products = await produitsResponse.json();
                console.log('✅ Produits chargés:', globalStockData.products.length);
            } else {
                console.error('❌ Erreur chargement produits:', produitsResponse.status);
                globalStockData.products = [];
            }
        } catch (error) {
            console.error('❌ Erreur produits:', error);
            globalStockData.products = [];
        }
        
        // Charger les stocks
        try {
            const stocksResponse = await fetch(entrepotManager.config.endpoints.stocks);
            if (stocksResponse.ok) {
                globalStockData.stocks = await stocksResponse.json();
                console.log('✅ Stocks chargés:', globalStockData.stocks.length);
            } else {
                console.error('❌ Erreur chargement stocks:', stocksResponse.status);
                globalStockData.stocks = [];
            }
        } catch (error) {
            console.error('❌ Erreur stocks:', error);
            globalStockData.stocks = [];
        }
        
        return true;
        
    } catch (error) {
        console.error('❌ Erreur globale chargement données:', error);
        return false;
    }
}

// ✅ FONCTION SÉPARÉE POUR SOUMETTRE
async function submitEntrepot(data, isEdit) {
    // Validation des données
    if (!data.nom || !data.adresse || !data.ville || !data.code_postal) {
        AdminCommon.utils.showAlert('❌ Veuillez remplir tous les champs obligatoires', 'error');
        return;
    }
    
    try {
        console.log('📡 Envoi vers API:', entrepotManager.config.endpoints.add);
        console.log('📋 Données envoyées:', data);
        
        const response = await fetch(entrepotManager.config.endpoints.add, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        console.log('📊 Status HTTP:', response.status);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📋 Résultat API:', result);
        
        if (result.success) {
            AdminCommon.utils.showAlert('✅ Entrepôt ajouté avec succès !', 'success');
            AdminCommon.utils.closeModal();
            
            await loadAllStockData();
            syncData();
            displayEntrepots();
            
        } else {
            AdminCommon.utils.showAlert('❌ Erreur API: ' + (result.message || 'Erreur inconnue'), 'error');
        }
        
    } catch (error) {
        console.error('❌ Erreur complète:', error);
        AdminCommon.utils.showAlert('❌ Erreur réseau: ' + error.message, 'error');
    }
}

function syncData() {
    console.log('🔄 Synchronisation des données...');
    entrepotManager.data.entrepots = Array.isArray(globalStockData.entrepots) ? globalStockData.entrepots : [];
    entrepotManager.data.stocks = Array.isArray(globalStockData.stocks) ? globalStockData.stocks : [];
    entrepotManager.data.produits = Array.isArray(globalStockData.products) ? globalStockData.products : [];
    console.log('✅ Données synchronisées:', entrepotManager.data);
}

function displayEntrepots() {
    console.log('🖼️ Affichage des entrepôts:', entrepotManager.data.entrepots);
    
    if (!Array.isArray(entrepotManager.data.entrepots) || entrepotManager.data.entrepots.length === 0) {
        document.getElementById('entrepots-table-container').innerHTML = `
            <div style="text-align: center; padding: 2rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
                <h3>🚨 Aucun entrepôt trouvé</h3>
                <p>Nombre d'entrepôts: ${entrepotManager.data.entrepots ? entrepotManager.data.entrepots.length : 'undefined'}</p>
                <button onclick="location.reload()" class="btn-primary">Recharger la page</button>
            </div>
        `;
        return;
    }
    
    AdminCommon.utils.createTable({
        containerId: 'entrepots-table-container',
        headers: entrepotManager.config.tables.entrepots.headers,
        data: entrepotManager.data.entrepots,
        rowBuilder: (entrepot) => {
            const row = document.createElement('tr');
            const cells = entrepotManager.config.tables.entrepots.rowBuilder(entrepot);
            row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
            return row;
        },
        emptyMessage: 'Aucun entrepôt trouvé'
    });
}

        async function editEntrepot(id) {
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
                    try {
                        data.id = id;
                        
                        const response = await fetch(entrepotManager.config.endpoints.update, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(data)
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            AdminCommon.utils.showAlert('Entrepôt modifié avec succès !', 'success');
                            AdminCommon.utils.closeModal();
                            
                            await loadAllStockData();
                            syncData();
                            displayEntrepots();
                            updateGlobalStats();
                        } else {
                            AdminCommon.utils.showAlert('Erreur : ' + result.message, 'error');
                        }
                        
                    } catch (error) {
                        console.error('Erreur lors de la modification:', error);
                        AdminCommon.utils.showAlert('Erreur réseau lors de la modification', 'error');
                    }
                }
            });
        }
    </script>
</body>
</html>