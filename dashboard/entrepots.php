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

// Variables globales
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
                { name: 'email', label: 'Email', type: 'email', attributes: 'placeholder="contact@entrepot.com"' },
                { name: 'responsable', label: 'Responsable', type: 'text', attributes: 'placeholder="Nom du responsable"' }
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

function getProduitOptions() {
    return [
        { value: '', text: 'Sélectionner un produit' },
        ...entrepotManager.data.produits.map(p => ({ value: p.id, text: `${p.nom} (${p.type})` }))
    ];
}

function showAddEntrepotModal() {
    console.log('🔄 Tentative d\'ouverture du modal...');
    
    // Vérifier que AdminCommon est disponible
    if (typeof AdminCommon === 'undefined' || !AdminCommon.utils) {
        console.error('❌ AdminCommon non disponible');
        alert('Erreur: Système non initialisé. Veuillez recharger la page.');
        return;
    }
    
    // Vérifier que createFormModal existe
    if (typeof AdminCommon.utils.createFormModal !== 'function') {
        console.error('❌ createFormModal non disponible');
        alert('Erreur: Fonction de modal non disponible.');
        return;
    }
    
    console.log('✅ AdminCommon disponible, création du modal...');
    
    try {
        AdminCommon.utils.createFormModal({
            title: 'Ajouter un entrepôt',
            fields: entrepotManager.config.formFields.entrepot,
            onSubmit: async (data, isEdit) => {
                console.log('📤 Soumission du formulaire:', data);
                await submitEntrepot(data, isEdit);
            }
        });
    } catch (error) {
        console.error('❌ Erreur lors de la création du modal:', error);
        alert('Erreur lors de l\'ouverture du formulaire: ' + error.message);
    }
}

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
        console.log('📊 Headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📋 Résultat API:', result);
        
        if (result.success) {
            AdminCommon.utils.showAlert('✅ Entrepôt ajouté avec succès !', 'success');
            AdminCommon.utils.closeModal();
            
            // Recharger les données
            await loadAllStockData();
            syncData();
            displayEntrepots();
            populateEntrepotFilter();
            
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