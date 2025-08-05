<?php
require_once '../includes/auth.php';
require_admin(); //
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des entrepôts - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/admin/stock-management.js"></script>
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
            
            <div id="alert-container"></div>
            
            <!-- Navigation rapide -->
            <div class="quick-nav">
                <a href="produits.php" class="btn-nav">Produits</a>
                <a href="#" class="btn-nav current">Entrepôts</a>
                <button onclick="showGlobalStockMatrix()" class="btn-nav">Vue globale</button>
                <button onclick="showStockAlerts()" class="btn-nav">Alertes</button>
                <button onclick="geocodeAllEntrepots()" class="btn-nav">Géolocaliser tous</button>
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
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('entrepots')">Entrepôts</button>
                <button class="tab-btn" onclick="switchTab('stocks')">Gestion des stocks</button>
            </div>

            <div id="tab-entrepots" class="tab-content active">
                <button class="add-btn" onclick="showAddEntrepotModal()">+ Ajouter un entrepôt</button>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Ville</th>
                            <th>Responsable</th>
                            <th>Nb produits</th>
                            <th>Alertes stock</th>
                            <th>Géolocalisation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="entrepots-table">
                    </tbody>
                </table>
            </div>

            <div id="tab-stocks" class="tab-content">
                <div style="margin-bottom: 1rem;">
                    <label for="filter-entrepot">Filtrer par entrepôt :</label>
                    <select id="filter-entrepot" onchange="loadStocksFiltered()" style="padding: 0.5rem; margin-left: 1rem;">
                        <option value="">Tous les entrepôts</option>
                    </select>
                    <button class="add-btn" onclick="showAddStockModal()" style="margin-left: 1rem;">+ Ajouter/Modifier stock</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Entrepôt</th>
                            <th>Produit</th>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Unité</th>
                            <th>Seuil d'alerte</th>
                            <th>État</th>
                            <th>Dernière MAJ</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stocks-table">
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Variables locales spécifiques aux entrepôts
        let entrepots = [];
        let stocks = [];
        let produits = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadAllData();
            startGlobalMonitoring();
        });

        async function loadAllData() {
            // Utiliser les fonctions communes
            await loadAllStockData();
            
            // Récupérer les données depuis le module commun
            entrepots = globalStockData.entrepots;
            stocks = globalStockData.stocks;
            produits = globalStockData.products;
            
            displayEntrepots();
            populateEntrepotFilter();
        }

        function populateEntrepotFilter() {
            const filterSelect = document.getElementById('filter-entrepot');
            filterSelect.innerHTML = `<option value="">Tous les entrepôts</option>`;
            entrepots.forEach(e => {
                filterSelect.innerHTML += `<option value="${e.id}">${e.nom}</option>`;
            });
        }

        // FONCTIONS SPÉCIFIQUES AUX ENTREPÔTS (non dupliquées)
        function displayEntrepots() {
            const tbody = document.getElementById('entrepots-table');
            tbody.innerHTML = '';
            
            entrepots.forEach(entrepot => {
                const entrepotStocks = stocks.filter(s => s.entrepot_id == entrepot.id);
                const nbProduits = entrepotStocks.length;
                const alertes = entrepotStocks.filter(s => s.quantite == 0 || s.alerte == 1).length;
                
                const coordonnees = entrepot.latitude && entrepot.longitude ? 
                    `<span style="color: #4caf50;">📍 Géolocalisé</span>` : 
                    `<span style="color: #f44336;">❌ Non géolocalisé</span>`;
                
                const alertesBadge = alertes > 0 ? 
                    `<span class="badge-danger">${alertes} alerte(s)</span>` :
                    `<span class="badge-success">Aucune alerte</span>`;
                
                tbody.innerHTML += `
                    <tr>
                        <td>${entrepot.id}</td>
                        <td><strong>${entrepot.nom}</strong><br>
                            <small style="color: #666;">${entrepot.responsable || 'Aucun responsable'}</small>
                        </td>
                        <td>${entrepot.adresse}</td>
                        <td>${entrepot.ville}<br>
                            <small style="color: #666;">${entrepot.code_postal}</small>
                        </td>
                        <td>${entrepot.responsable || '-'}</td>
                        <td>${nbProduits} produit(s)</td>
                        <td>${alertesBadge}</td>
                        <td>${coordonnees}</td>
                        <td>
                            <button class="btn-action" onclick="editEntrepot(${entrepot.id})">Modifier</button>
                            <button class="btn-action" onclick="viewEntrepotStocks(${entrepot.id})">Stocks</button>
                            <button class="btn-action danger" onclick="deleteEntrepot(${entrepot.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(`tab-${tab}`).classList.add('active');
            event.target.classList.add('active');
        }

        function showAddEntrepotModal() {
            showEntrepotModal();
        }

        function editEntrepot(id) {
            const entrepot = entrepots.find(e => e.id == id);
            if (entrepot) {
                showEntrepotModal(entrepot);
            }
        }

        // GESTION DES STOCKS FILTRÉS
        async function loadStocksFiltered() {
            const entrepotId = document.getElementById('filter-entrepot').value;
            
            try {
                const url = entrepotId ? 
                    `../api/stocks/list.php?entrepot_id=${entrepotId}` :
                    '../api/stocks/list.php';
                const response = await fetch(url);
                stocks = await response.json();
                
                displayStocks();
                updateGlobalStats();
            } catch (error) {
                console.error('Erreur lors du chargement des stocks:', error);
            }
        }

        function displayStocks() {
            const tbody = document.getElementById('stocks-table');
            tbody.innerHTML = '';
            
            stocks.forEach(stock => {
                const etat = stock.quantite == 0 ? 'Rupture' :
                           stock.alerte == 1 ? 'Alerte' :
                           'OK';
                const classeEtat = stock.quantite == 0 ? 'badge-danger' :
                                 stock.alerte == 1 ? 'badge-warning' :
                                 'badge-success';
                
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${getEntrepotName(stock.entrepot_id)}</strong></td>
                        <td>${getProduitName(stock.produit_id)}</td>
                        <td><span class="type-badge type-${stock.produit_type}">${stock.produit_type}</span></td>
                        <td><strong>${parseFloat(stock.quantite).toFixed(2)}</strong></td>
                        <td>${stock.unite}</td>
                        <td>${parseFloat(stock.seuil_alerte).toFixed(2)}</td>
                        <td><span class="${classeEtat}" style="padding: 0.3rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">${etat}</span></td>
                        <td>${new Date(stock.derniere_maj).toLocaleDateString('fr-FR')} ${new Date(stock.derniere_maj).toLocaleTimeString('fr-FR')}</td>
                        <td>
                            <button class="btn-action" onclick="editStock(${stock.entrepot_id}, ${stock.produit_id})">Modifier</button>
                            <button class="btn-action danger" onclick="deleteStock(${stock.entrepot_id}, ${stock.produit_id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        // MODAL ENTREPÔT AVEC GÉOLOCALISATION
        function showEntrepotModal(entrepot = null) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>${entrepot ? 'Modifier' : 'Ajouter'} un entrepôt</h3>
                    <form id="entrepot-form">
                        <div class="form-group">
                            <label for="nom">Nom de l'entrepôt *</label>
                            <input type="text" id="nom" name="nom" value="${entrepot ? entrepot.nom : ''}" required>
                        </div>
                        <div class="form-group">
                            <label for="adresse">Adresse complète *</label>
                            <input type="text" id="adresse" name="adresse" value="${entrepot ? entrepot.adresse : ''}" required
                                   placeholder="123 Rue de la Logistique">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ville">Ville *</label>
                                <input type="text" id="ville" name="ville" value="${entrepot ? entrepot.ville : ''}" required>
                            </div>
                            <div class="form-group">
                                <label for="code_postal">Code postal *</label>
                                <input type="text" id="code_postal" name="code_postal" value="${entrepot ? entrepot.code_postal : ''}" required
                                       pattern="[0-9]{5}" title="Code postal français (5 chiffres)">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="${entrepot ? entrepot.telephone || '' : ''}"
                                       placeholder="01 23 45 67 89">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="${entrepot ? entrepot.email || '' : ''}"
                                       placeholder="entrepot@drivncook.com">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="responsable">Responsable</label>
                            <input type="text" id="responsable" name="responsable" value="${entrepot ? entrepot.responsable || '' : ''}"
                                   placeholder="Jean Dupont">
                        </div>
                        
                        <!-- Section géolocalisation -->
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="auto-geolocate" checked> 
                                <label for="auto-geolocate">Géolocaliser automatiquement l'adresse</label>
                            </div>
                            <small class="geocoding-info">
                                Recommandé pour optimiser les calculs de distance et livraisons
                            </small>
                        </div>
                        
                        <div id="coordinates-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="latitude">Latitude</label>
                                    <input type="number" id="latitude" name="latitude" step="any" 
                                           value="${entrepot && entrepot.latitude ? entrepot.latitude : ''}" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="longitude">Longitude</label>
                                    <input type="number" id="longitude" name="longitude" step="any" 
                                           value="${entrepot && entrepot.longitude ? entrepot.longitude : ''}" readonly>
                                </div>
                            </div>
                            <button type="button" id="manual-geocode" class="btn-secondary" style="margin-bottom: 1rem;">
                                Géolocaliser maintenant
                            </button>
                        </div>
                        
                        <div id="geocoding-status"></div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">${entrepot ? 'Modifier' : 'Ajouter'}</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gestion de l'affichage des coordonnées
            const autoGeolocateCheck = document.getElementById('auto-geolocate');
            const coordinatesSection = document.getElementById('coordinates-section');
            
            autoGeolocateCheck.addEventListener('change', function() {
                coordinatesSection.style.display = this.checked ? 'none' : 'block';
            });
            
            // Géolocalisation manuelle
            document.getElementById('manual-geocode').addEventListener('click', async function() {
                await performGeocoding();
            });
            
            // Géolocalisation automatique lors de la saisie
            let geocodeTimeout;
            ['adresse', 'ville', 'code_postal'].forEach(fieldId => {
                document.getElementById(fieldId).addEventListener('input', function() {
                    if (autoGeolocateCheck.checked) {
                        clearTimeout(geocodeTimeout);
                        geocodeTimeout = setTimeout(async () => {
                            await performGeocoding();
                        }, 1000);
                    }
                });
            });
            
            // Si on modifie un entrepôt existant, afficher les coordonnées si disponibles
            if (entrepot && entrepot.latitude && entrepot.longitude) {
                autoGeolocateCheck.checked = false;
                coordinatesSection.style.display = 'block';
                updateGeocodingStatus(`Coordonnées actuelles : ${entrepot.latitude}, ${entrepot.longitude}`, 'success');
            }
            
            // Soumission du formulaire
            document.getElementById('entrepot-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await submitEntrepot(entrepot);
            });
        }

        // GÉOLOCALISATION SPÉCIFIQUE
        async function performGeocoding() {
            const adresse = document.getElementById('adresse').value;
            const ville = document.getElementById('ville').value;
            const codePostal = document.getElementById('code_postal').value;
            
            if (!adresse || !ville || !codePostal) {
                updateGeocodingStatus('Veuillez remplir l\'adresse, la ville et le code postal', 'warning');
                return;
            }

            updateGeocodingStatus('Géolocalisation en cours...', 'info');

            const fullAddress = `${adresse}, ${codePostal} ${ville}, France`;
            const result = await geocodeAddress(fullAddress);
            
            if (result.success) {
                document.getElementById('latitude').value = result.latitude;
                document.getElementById('longitude').value = result.longitude;
                updateGeocodingStatus(`Géolocalisé avec succès : ${result.latitude.toFixed(6)}, ${result.longitude.toFixed(6)}`, 'success');
            } else {
                updateGeocodingStatus(`Échec de la géolocalisation : ${result.error}`, 'error');
            }
        }

        function updateGeocodingStatus(message, type) {
            const statusDiv = document.getElementById('geocoding-status');
            const colors = {
                'success': '#d4edda',
                'error': '#f8d7da',
                'warning': '#fff3cd',
                'info': '#d1ecf1'
            };
            
            statusDiv.innerHTML = `
                <div style="padding: 0.5rem; background: ${colors[type] || colors.info}; border-radius: 4px; font-size: 0.9rem;">
                    ${message}
                </div>
            `;
        }

        async function submitEntrepot(entrepot = null) {
            const formData = new FormData(document.getElementById('entrepot-form'));
            
            const data = {
                nom: formData.get('nom'),
                adresse: formData.get('adresse'),
                ville: formData.get('ville'),
                code_postal: formData.get('code_postal'),
                telephone: formData.get('telephone'),
                email: formData.get('email'),
                responsable: formData.get('responsable')
            };
            
            // Ajouter les coordonnées si disponibles
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (latitude && longitude) {
                data.latitude = parseFloat(latitude);
                data.longitude = parseFloat(longitude);
            } else if (document.getElementById('auto-geolocate').checked) {
                // Géolocaliser une dernière fois avant soumission
                updateGeocodingStatus('Géolocalisation finale...', 'info');
                const fullAddress = `${data.adresse}, ${data.code_postal} ${data.ville}, France`;
                const result = await geocodeAddress(fullAddress);
                
                if (result.success) {
                    data.latitude = result.latitude;
                    data.longitude = result.longitude;
                    updateGeocodingStatus(`Géolocalisé pour sauvegarde`, 'success');
                } else {
                    updateGeocodingStatus(`Sauvegarde sans géolocalisation`, 'warning');
                }
            }
            
            if (entrepot) {
                data.id = entrepot.id;
            }
            
            try {
                const url = entrepot ? '../api/entrepots/update.php' : '../api/entrepots/add.php';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal();
                    loadAllData();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la sauvegarde', 'error');
                console.error('Erreur:', error);
            }
        }

        // MODAL STOCKS D'UN ENTREPÔT SPÉCIFIQUE
        function viewEntrepotStocks(entrepotId) {
            const entrepot = entrepots.find(e => e.id == entrepotId);
            const entrepotStocks = stocks.filter(s => s.entrepot_id == entrepotId);
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content large">
                    <h3>Stocks de l'entrepôt "${entrepot.nom}"</h3>
                    
                    ${entrepotStocks.length === 0 ? 
                        '<p style="text-align: center; color: #f44336; font-weight: bold; margin: 2rem 0;">Aucun stock défini pour cet entrepôt</p>' :
                        `<table style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Type</th>
                                    <th>Quantité</th>
                                    <th>Seuil</th>
                                    <th>État</th>
                                    <th>Dernière MAJ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entrepotStocks.map(stock => {
                                    const etat = stock.quantite == 0 ? 'Rupture' :
                                               stock.alerte == 1 ? 'Alerte' :
                                               'OK';
                                    const classeEtat = stock.quantite == 0 ? 'badge-danger' :
                                                     stock.alerte == 1 ? 'badge-warning' :
                                                     'badge-success';
                                    
                                    return `
                                        <tr>
                                            <td><strong>${getProduitName(stock.produit_id)}</strong></td>
                                            <td><span class="type-badge type-${stock.produit_type}">${stock.produit_type}</span></td>
                                            <td><strong>${parseFloat(stock.quantite).toFixed(2)} ${stock.unite}</strong></td>
                                            <td>${parseFloat(stock.seuil_alerte).toFixed(2)} ${stock.unite}</td>
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
                        <button type="button" class="btn-primary" onclick="closeModal(); switchTab('stocks'); document.getElementById('filter-entrepot').value='${entrepotId}'; loadStocksFiltered();">Gérer les stocks</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // ACTIONS SPÉCIFIQUES
        async function deleteEntrepot(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet entrepôt ? Tous les stocks associés seront également supprimés.')) {
                return;
            }
            
            try {
                const response = await fetch('../api/entrepots/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Entrepôt supprimé avec succès !', 'success');
                    loadAllData();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la suppression', 'error');
                console.error('Erreur:', error);
            }
        }

        // Fonctions de gestion des stocks individuels
        function showAddStockModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter/Modifier un stock</h3>
                    <form id="stock-form">
                        <div class="form-group">
                            <label for="stock-entrepot">Entrepôt *</label>
                            <select id="stock-entrepot" name="entrepot_id" required>
                                <option value="">Sélectionner un entrepôt</option>
                                ${entrepots.map(e => `<option value="${e.id}">${e.nom}</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock-produit">Produit *</label>
                            <select id="stock-produit" name="produit_id" required>
                                <option value="">Sélectionner un produit</option>
                                ${produits.map(p => `<option value="${p.id}" data-type="${p.type}">${p.nom} (${p.type})</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock-quantite">Quantité *</label>
                                <input type="number" id="stock-quantite" name="quantite" step="0.001" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="stock-unite">Unité *</label>
                                <select id="stock-unite" name="unite" required>
                                    <option value="kg">Kilogrammes</option>
                                    <option value="litres">Litres</option>
                                    <option value="unites">Unités</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock-seuil">Seuil d'alerte</label>
                            <input type="number" id="stock-seuil" name="seuil_alerte" step="0.001" min="0" value="10">
                            <small style="color: #666;">Quantité en dessous de laquelle une alerte sera déclenchée</small>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Mise à jour automatique de l'unité selon le type de produit
            document.getElementById('stock-produit').addEventListener('change', function() {
                const selectedOption = this.selectedOptions[0];
                const type = selectedOption?.dataset.type;
                const uniteSelect = document.getElementById('stock-unite');
                
                if (type) {
                    switch(type) {
                        case 'aliment':
                            uniteSelect.value = 'kg';
                            break;
                        case 'boisson':
                            uniteSelect.value = 'litres';
                            break;
                        case 'préparé':
                            uniteSelect.value = 'unites';
                            break;
                    }
                }
            });
            
            // Soumission du formulaire
            document.getElementById('stock-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await submitStock();
            });
        }

        async function submitStock() {
            const formData = new FormData(document.getElementById('stock-form'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/stocks/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Stock enregistré avec succès !', 'success');
                    closeModal();
                    await loadAllData();
                    displayStocks();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de l\'enregistrement:', error);
                showAlert('Erreur réseau lors de l\'enregistrement', 'error');
            }
        }

        function editStock(entrepotId, produitId) {
            const stock = stocks.find(s => s.entrepot_id == entrepotId && s.produit_id == produitId);
            if (!stock) {
                showAlert('Stock non trouvé', 'error');
                return;
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Modifier le stock</h3>
                    <form id="edit-stock-form">
                        <div class="form-group">
                            <label>Entrepôt</label>
                            <input type="text" value="${getEntrepotName(entrepotId)}" disabled>
                            <input type="hidden" name="entrepot_id" value="${entrepotId}">
                        </div>
                        
                        <div class="form-group">
                            <label>Produit</label>
                            <input type="text" value="${getProduitName(produitId)}" disabled>
                            <input type="hidden" name="produit_id" value="${produitId}">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-quantite">Quantité *</label>
                                <input type="number" id="edit-quantite" name="quantite" step="0.001" min="0" 
                                       value="${stock.quantite}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-unite">Unité *</label>
                                <select id="edit-unite" name="unite" required>
                                    <option value="kg" ${stock.unite === 'kg' ? 'selected' : ''}>Kilogrammes</option>
                                    <option value="litres" ${stock.unite === 'litres' ? 'selected' : ''}>Litres</option>
                                    <option value="unites" ${stock.unite === 'unites' ? 'selected' : ''}>Unités</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-seuil">Seuil d'alerte</label>
                            <input type="number" id="edit-seuil" name="seuil_alerte" step="0.001" min="0" 
                                   value="${stock.seuil_alerte}">
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Soumission du formulaire
            document.getElementById('edit-stock-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await updateStock();
            });
        }

        async function updateStock() {
            const formData = new FormData(document.getElementById('edit-stock-form'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/stocks/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Stock modifié avec succès !', 'success');
                    closeModal();
                    await loadAllData();
                    displayStocks();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                showAlert('Erreur réseau lors de la modification', 'error');
            }
        }

        async function deleteStock(entrepotId, produitId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce stock ?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/stocks/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        entrepot_id: entrepotId,
                        produit_id: produitId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Stock supprimé avec succès !', 'success');
                    await loadAllData();
                    displayStocks();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
                
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                showAlert('Erreur réseau lors de la suppression', 'error');
            }
        }
    </script>
</body>
</html>