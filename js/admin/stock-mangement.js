
// VARIABLES GLOBALES PARTAGÉES

let globalStockData = {
    products: [],
    entrepots: [],
    stocks: [],
    globalStats: {
        ruptures: 0,
        alertes: 0,
        stocksOk: 0,
        totalEntrepots: 0,
        totalProduits: 0
    }
};

// FONCTIONS DE CHARGEMENT DES DONNÉES

/**
 * Charger tous les produits
 */
async function loadProducts() {
    try {
        const response = await fetch('../api/produits/list.php');
        globalStockData.products = await response.json();
        return globalStockData.products;
    } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
        return [];
    }
}

/**
 * Charger tous les entrepôts
 */
async function loadEntrepots() {
    try {
        const response = await fetch('../api/entrepots/list.php');
        globalStockData.entrepots = await response.json();
        return globalStockData.entrepots;
    } catch (error) {
        console.error('Erreur lors du chargement des entrepôts:', error);
        return [];
    }
}

/**
 * Charger tous les stocks
 */
async function loadStocks() {
    try {
        const response = await fetch('../api/stocks/list.php');
        globalStockData.stocks = await response.json();
        return globalStockData.stocks;
    } catch (error) {
        console.error('Erreur lors du chargement des stocks:', error);
        return [];
    }
}

/**
 * Charger toutes les données en parallèle
 */
async function loadAllStockData() {
    await Promise.all([
        loadProducts(),
        loadEntrepots(),
        loadStocks()
    ]);
    updateGlobalStats();
    return globalStockData;
}

// FONCTIONS DE CALCUL ET STATISTIQUES

/**
 * Mettre à jour les statistiques globales
 */
function updateGlobalStats() {
    const stats = globalStockData.globalStats;
    const stocks = globalStockData.stocks;
    
    stats.ruptures = stocks.filter(s => s.quantite == 0).length;
    stats.alertes = stocks.filter(s => s.alerte == 1 && s.quantite > 0).length;
    stats.stocksOk = stocks.filter(s => s.alerte == 0 && s.quantite > 0).length;
    stats.totalEntrepots = globalStockData.entrepots.length;
    stats.totalProduits = globalStockData.products.length;
    
    // Mettre à jour l'affichage si les éléments existent
    updateGlobalStatsDisplay();
}

/**
 * Mettre à jour l'affichage des statistiques globales
 */
function updateGlobalStatsDisplay() {
    const stats = globalStockData.globalStats;
    const elements = {
        'global-ruptures': stats.ruptures,
        'global-alertes': stats.alertes,
        'global-stocks-ok': stats.stocksOk,
        'global-entrepots': stats.totalEntrepots,
        'global-produits': stats.totalProduits
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

/**
 * Obtenir le badge de statut pour un produit
 */
function getStockStatusBadge(productStocks) {
    if (productStocks.length === 0) {
        return '<span class="badge-danger">Aucun stock</span>';
    }
    
    const ruptures = productStocks.filter(s => s.quantite == 0);
    const alertes = productStocks.filter(s => s.alerte == 1 && s.quantite > 0);
    const ok = productStocks.filter(s => s.alerte == 0 && s.quantite > 0);
    
    if (ruptures.length > 0) {
        return `<span class="badge-danger">Rupture (${ruptures.length})</span>`;
    } else if (alertes.length > 0) {
        return `<span class="badge-warning">Alerte (${alertes.length})</span>`;
    } else {
        return `<span class="badge-success">OK (${ok.length})</span>`;
    }
}

// FONCTIONS UTILITAIRES

/**
 * Obtenir le nom d'un entrepôt par son ID
 */
function getEntrepotName(id) {
    const entrepot = globalStockData.entrepots.find(e => e.id == id);
    return entrepot ? entrepot.nom : 'Inconnu';
}

/**
 * Obtenir le nom d'un produit par son ID
 */
function getProduitName(id) {
    const produit = globalStockData.products.find(p => p.id == id);
    return produit ? produit.nom : 'Inconnu';
}

/**
 * Obtenir un produit par son ID
 */
function getProduitById(id) {
    return globalStockData.products.find(p => p.id == id);
}

/**
 * Obtenir un entrepôt par son ID
 */
function getEntrepotById(id) {
    return globalStockData.entrepots.find(e => e.id == id);
}

/**
 * Formater un prix
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2) + '€';
}

/**
 * Formater une quantité avec unité
 */
function formatQuantity(quantity, unit) {
    return `${parseFloat(quantity).toFixed(2)} ${unit}`;
}

// FONCTIONS D'AFFICHAGE DES MODALES

/**
 * Afficher la matrice globale des stocks
 */
function showGlobalStockMatrix() {
    const modal = document.createElement('div');
    modal.className = 'modal modal-large';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>Matrice globale des stocks</h3>
            <div style="overflow-x: auto;">
                <table style="min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="position: sticky; left: 0; background: #f8f9fa; z-index: 10;">Produit</th>
                            ${globalStockData.entrepots.map(e => `<th>${e.nom}</th>`).join('')}
                            <th>Total entrepôts</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${globalStockData.products.map(product => {
                            const productStocks = globalStockData.stocks.filter(s => s.produit_id == product.id);
                            return `
                                <tr>
                                    <td style="position: sticky; left: 0; background: white; font-weight: bold; z-index: 5;">
                                        ${product.nom}
                                        ${product.obligatoire ? '<span class="badge badge-obligatoire" style="margin-left: 0.5rem;">Obligatoire</span>' : ''}
                                    </td>
                                    ${globalStockData.entrepots.map(entrepot => {
                                        const stock = productStocks.find(s => s.entrepot_id == entrepot.id);
                                        if (!stock) {
                                            return '<td style="text-align: center; color: #ccc;">-</td>';
                                        }
                                        const badgeClass = stock.quantite == 0 ? 'badge-danger' : 
                                                         stock.alerte == 1 ? 'badge-warning' : 'badge-success';
                                        return `<td style="text-align: center;">
                                            <span class="${badgeClass}" style="padding: 0.2rem 0.4rem; border-radius: 8px; font-size: 0.75rem;">
                                                ${stock.quantite} ${stock.unite}
                                            </span>
                                        </td>`;
                                    }).join('')}
                                    <td style="font-weight: bold; text-align: center;">
                                        ${productStocks.length}/${globalStockData.entrepots.length}
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                <button type="button" class="btn-primary" onclick="closeModal(); window.location.href='entrepots.php'">Gérer les stocks</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

/**
 * Afficher les alertes de stock détaillées
 */
function showStockAlerts() {
    const criticalAlerts = [];
    const warningAlerts = [];
    
    globalStockData.products.forEach(product => {
        const productStocks = globalStockData.stocks.filter(s => s.produit_id == product.id);
        const ruptures = productStocks.filter(s => s.quantite == 0);
        const alertes = productStocks.filter(s => s.alerte == 1 && s.quantite > 0);
        
        if (ruptures.length > 0) {
            criticalAlerts.push({
                produit: product.nom,
                entrepots: ruptures.map(r => getEntrepotName(r.entrepot_id)).join(', '),
                type: 'Rupture de stock'
            });
        }
        
        if (alertes.length > 0) {
            warningAlerts.push({
                produit: product.nom,
                entrepots: alertes.map(a => `${getEntrepotName(a.entrepot_id)} (${a.quantite} ${a.unite})`).join(', '),
                type: 'Stock faible'
            });
        }
    });
    
    const modal = document.createElement('div');
    modal.className = 'modal modal-large';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>🚨 Alertes détaillées (${criticalAlerts.length + warningAlerts.length} au total)</h3>
            
            ${criticalAlerts.length > 0 ? `
                <h4 style="color: #f44336; margin-top: 1.5rem;">Ruptures de stock (${criticalAlerts.length})</h4>
                ${criticalAlerts.map(alert => `
                    <div style="border-left: 4px solid #f44336; padding: 1rem; margin-bottom: 0.5rem; background: #ffebee;">
                        <strong>${alert.produit}</strong><br>
                        <small>Entrepôts concernés: ${alert.entrepots}</small>
                    </div>
                `).join('')}
            ` : ''}
            
            ${warningAlerts.length > 0 ? `
                <h4 style="color: #ff9800; margin-top: 1.5rem;">Stocks faibles (${warningAlerts.length})</h4>
                ${warningAlerts.map(alert => `
                    <div style="border-left: 4px solid #ff9800; padding: 1rem; margin-bottom: 0.5rem; background: #fff3e0;">
                        <strong>${alert.produit}</strong><br>
                        <small>Entrepôts concernés: ${alert.entrepots}</small>
                    </div>
                `).join('')}
            ` : ''}
            
            ${criticalAlerts.length === 0 && warningAlerts.length === 0 ? 
                '<p style="text-align: center; color: #4caf50; font-weight: bold; margin: 2rem 0;">Aucune alerte ! Tous les stocks sont en bon état.</p>' : ''
            }
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                <button type="button" class="btn-primary" onclick="closeModal(); window.location.href='entrepots.php'">Gérer les stocks</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

/**
 * Afficher les stocks d'un produit spécifique
 */
function viewProductStocks(productId) {
    const product = getProduitById(productId);
    const productStocks = globalStockData.stocks.filter(s => s.produit_id == productId);
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <h3>Stocks de "${product.nom}"</h3>
            ${product.obligatoire ? '<div class="badge badge-obligatoire" style="margin-bottom: 1rem;">Produit obligatoire</div>' : ''}
            ${product.quantite_minimale > 0 ? `<div style="background: #f0f8ff; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem;">Quantité minimale imposée: ${product.quantite_minimale}</div>` : ''}
            
            ${productStocks.length === 0 ? 
                '<p style="text-align: center; color: #f44336; font-weight: bold; margin: 2rem 0;">Aucun stock défini pour ce produit</p>' :
                `<table style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Entrepôt</th>
                            <th>Quantité</th>
                            <th>Seuil d'alerte</th>
                            <th>État</th>
                            <th>Dernière MAJ</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${productStocks.map(stock => {
                            const etat = stock.quantite == 0 ? 'Rupture' :
                                       stock.alerte == 1 ? 'Alerte' :
                                       'OK';
                            const classeEtat = stock.quantite == 0 ? 'badge-danger' :
                                             stock.alerte == 1 ? 'badge-warning' :
                                             'badge-success';
                            
                            return `
                                <tr>
                                    <td><strong>${getEntrepotName(stock.entrepot_id)}</strong></td>
                                    <td>${formatQuantity(stock.quantite, stock.unite)}</td>
                                    <td>${formatQuantity(stock.seuil_alerte, stock.unite)}</td>
                                    <td><span class="${classeEtat}" style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem;">${etat}</span></td>
                                    <td>${new Date(stock.derniere_maj).toLocaleDateString('fr-FR')}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>`
            }
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                <button type="button" class="btn-primary" onclick="closeModal(); window.location.href='entrepots.php'">Gérer les stocks</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// FONCTIONS DE GÉOLOCALISATION

/**
 * Géolocaliser une adresse avec l'API Nominatim
 */
async function geocodeAddress(address) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
        const data = await response.json();
        
        if (data.length > 0) {
            return {
                latitude: parseFloat(data[0].lat),
                longitude: parseFloat(data[0].lon),
                success: true
            };
        } else {
            return { success: false, error: 'Adresse non trouvée' };
        }
    } catch (error) {
        console.error('Erreur de géocodage:', error);
        return { success: false, error: 'Erreur de géocodage' };
    }
}

/**
 * Géolocaliser tous les entrepôts non géolocalisés
 */
async function geocodeAllEntrepots() {
    const entrepotsSansCoordonnees = globalStockData.entrepots.filter(e => !e.latitude || !e.longitude);
    
    if (entrepotsSansCoordonnees.length === 0) {
        showAlert('Tous les entrepôts sont déjà géolocalisés !', 'success');
        return;
    }
    
    if (!confirm(`Géolocaliser ${entrepotsSansCoordonnees.length} entrepôt(s) ? Cette opération peut prendre quelques minutes.`)) {
        return;
    }
    
    let processed = 0;
    let success = 0;
    
    for (const entrepot of entrepotsSansCoordonnees) {
        try {
            showAlert(`Géolocalisation ${processed + 1}/${entrepotsSansCoordonnees.length}: ${entrepot.nom}...`, 'info');
            
            const fullAddress = `${entrepot.adresse}, ${entrepot.code_postal} ${entrepot.ville}, France`;
            const result = await geocodeAddress(fullAddress);
            
            if (result.success) {
                // Mettre à jour en base
                const response = await fetch('../api/entrepots/update.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: entrepot.id,
                        nom: entrepot.nom,
                        adresse: entrepot.adresse,
                        ville: entrepot.ville,
                        code_postal: entrepot.code_postal,
                        telephone: entrepot.telephone,
                        email: entrepot.email,
                        responsable: entrepot.responsable,
                        latitude: result.latitude,
                        longitude: result.longitude
                    })
                });
                
                if (response.ok) {
                    success++;
                }
            }
            
            processed++;
            
            // Pause pour éviter de surcharger l'API
            await new Promise(resolve => setTimeout(resolve, 1000));
            
        } catch (error) {
            console.error(`Erreur pour ${entrepot.nom}:`, error);
            processed++;
        }
    }
    
    showAlert(`Géolocalisation terminée : ${success}/${processed} entrepôts traités avec succès`, success > 0 ? 'success' : 'error');
    await loadAllStockData();
    
    // Recharger les affichages si les fonctions existent
    if (typeof displayEntrepots === 'function') displayEntrepots();
    if (typeof displayProducts === 'function') displayProducts();
}

// FONCTIONS UTILITAIRES GÉNÉRALES

/**
 * Fermer la modal active
 */
function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) modal.remove();
}

/**
 * Afficher une alerte
 */
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-error', 
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    alertContainer.innerHTML = `
        <div class="alert ${alertClass}">
            ${message}
        </div>
    `;
    
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}

/**
 * Démarrer le monitoring automatique
 */
function startGlobalMonitoring() {
    setInterval(async () => {
        await loadStocks();
        updateGlobalStats();
        
        // Recharger les affichages si les fonctions existent
        if (typeof displayProducts === 'function') displayProducts();
        if (typeof displayEntrepots === 'function') displayEntrepots();
        if (typeof displayStocks === 'function') displayStocks();
    }, 30000);
}

// FONCTIONS DE MISE À JOUR DE L'INTERFACE

/**
 * Mettre à jour le sélecteur d'unités selon le type de produit
 */
function updateUniteOptions() {
    const typeSelect = document.getElementById('type');
    const uniteSelects = document.querySelectorAll('.unite-select');
    
    if (!typeSelect || uniteSelects.length === 0) return;
    
    const type = typeSelect.value;
    const unitesByType = {
        'aliment': ['kg', 'unites'],
        'boisson': ['litres', 'unites'],
        'préparé': ['unites']
    };
    
    const unites = unitesByType[type] || ['kg', 'litres', 'unites'];
    
    uniteSelects.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '';
        
        unites.forEach(unite => {
            const unitLabels = {
                'kg': 'Kilogrammes',
                'litres': 'Litres', 
                'unites': 'Unités'
            };
            
            const option = document.createElement('option');
            option.value = unite;
            option.textContent = unitLabels[unite];
            
            if (unite === currentValue || (unites.length === 1 && unite === unites[0])) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
    });
}

/**
 * Activer/désactiver le champ quantité minimale selon le statut obligatoire
 */
function toggleQuantiteMinimale() {
    const obligatoire = document.getElementById('obligatoire');
    const container = document.getElementById('quantite-minimale-container');
    const input = document.getElementById('quantite_minimale');
    
    if (!obligatoire || !container || !input) return;
    
    if (obligatoire.checked) {
        container.style.display = 'block';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = 0;
    }
}

// GESTION DES ÉVÉNEMENTS GLOBAUX

// Fermeture modal sur clic extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

// Export des fonctions pour utilisation dans d'autres scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        globalStockData,
        loadAllStockData,
        updateGlobalStats,
        getStockStatusBadge,
        showGlobalStockMatrix,
        showStockAlerts,
        viewProductStocks,
        geocodeAddress,
        geocodeAllEntrepots,
        closeModal,
        showAlert,
        startGlobalMonitoring,
        updateUniteOptions,
        toggleQuantiteMinimale
    };
}