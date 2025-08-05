<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commandes de stock - Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="menu.php">Mon menu</a>
            <a href="commandes.php" class="active">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content franchise">
            <h1 style="color:#e64a19;">Commandes de stock</h1>
            
            <div id="alert-container"></div>
            
            <div class="tabs">
                <button class="tab-btn franchise active" onclick="switchTab('nouvelle')">Nouvelle commande</button>
                <button class="tab-btn franchise" onclick="switchTab('historique')">Historique</button>
            </div>

            <!-- Onglet Nouvelle commande -->
            <div id="tab-nouvelle" class="tab-content active">
                <div class="section-card">
                    <h2>Créer une nouvelle commande</h2>
                    
                    <form id="commande-form">
                        <div class="form-group">
                            <label for="entrepot-select">Entrepôt de livraison *</label>
                            <select id="entrepot-select" required>
                                <option value="">Sélectionner un entrepôt</option>
                            </select>
                        </div>
                        
                        <div id="produits-container" style="display:none;">
                            <h3>Sélection des produits</h3>
                            
                            <div id="alerte-quantite-minimale" class="alert alert-info" style="display:none;">
                                ⚠️ <strong>Attention :</strong> Certains produits obligatoires ont une quantité minimale à respecter.
                                <div id="details-quantite-minimale"></div>
                            </div>
                            
                            <div id="produits-list"></div>
                            
                            <div id="commande-resume" class="commande-resume" style="display:none;">
                                <h4>Résumé de la commande</h4>
                                <div id="resume-items"></div>
                                <div class="commande-totaux">
                                    <div><strong>Total : <span id="total-commande">0.00€</span></strong></div>
                                </div>
                                <div style="margin-top: 1.5rem;">
                                    <button type="submit" class="btn">Passer la commande</button>
                                    <button type="button" class="btn-secondary" onclick="resetCommande()">Réinitialiser</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Onglet Historique -->
            <div id="tab-historique" class="tab-content">
                <div class="section-card">
                    <h2>Historique des commandes</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Entrepôt</th>
                                <th>Nb produits</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Produits</th>
                            </tr>
                        </thead>
                        <tbody id="historique-commandes">
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        let entrepots = [];
        let produits = [];
        let commandeActuelle = [];
        let totalCommande = 0;

        // Chargement initial
        document.addEventListener('DOMContentLoaded', function() {
            loadEntrepots();
            loadHistorique();
        });

        // Charger les entrepôts
        async function loadEntrepots() {
            try {
                // Essayer d'obtenir la position de l'utilisateur
                let userPosition = null;
                try {
                    userPosition = await getCurrentPosition();
                    showAlert('📍 Position détectée, tri par proximité activé', 'success');
                } catch (error) {
                    console.log('Géolocalisation non disponible:', error.message);
                    showAlert('Géolocalisation non disponible, affichage par ordre alphabétique', 'info');
                }
                
                // Charger les entrepôts avec ou sans géolocalisation
                const url = userPosition ? 
                    `../api/entrepots/plus_proche.php?lat=${userPosition.latitude}&lng=${userPosition.longitude}` :
                    '../api/entrepots/list.php';
                    
                const response = await fetch(url);
                entrepots = await response.json();
                
                displayEntrepots(userPosition !== null);
                
                // Remplir le filtre
                const select = document.getElementById('entrepot-select');
                select.innerHTML = '<option value="">Sélectionner un entrepôt</option>';
                
                entrepots.forEach((entrepot, index) => {
                    const distanceText = entrepot.distance_km ? 
                        ` (${formatDistance(entrepot.distance_km)})` : '';
                    const recommendationBadge = index === 0 && entrepot.distance_km ? ' 🌟' : '';
                    
                    select.innerHTML += `
                        <option value="${entrepot.id}">
                            ${entrepot.nom} - ${entrepot.ville}${distanceText}${recommendationBadge}
                        </option>
                    `;
                });
                
            } catch (error) {
                showAlert('Erreur lors du chargement des entrepôts', 'error');
                console.error('Erreur:', error);
            }
        }

        function displayEntrepots(hasGeolocation = false) {
            // Ajouter un message informatif si géolocalisé
            if (hasGeolocation && entrepots.length > 0 && entrepots[0].distance_km) {
                const infoContainer = document.getElementById('entrepot-select').parentNode;
                
                // Supprimer l'ancien message s'il existe
                const oldInfo = infoContainer.querySelector('.proximity-info');
                if (oldInfo) oldInfo.remove();
                
                // Ajouter le nouveau message
                const infoDiv = document.createElement('div');
                infoDiv.className = 'proximity-info';
                infoDiv.style.cssText = `
                    background: #e8f5e8; 
                    border: 1px solid #4caf50; 
                    border-radius: 6px; 
                    padding: 0.8rem; 
                    margin-top: 0.5rem; 
                    font-size: 0.9rem;
                `;
                infoDiv.innerHTML = `
                    📍 <strong>Entrepôts triés par proximité</strong><br>
                    <small>Le plus proche est à ${formatDistance(entrepots[0].distance_km)} 
                    (livraison estimée : ${entrepots[0].temps_livraison_estime})</small>
                `;
                
                infoContainer.appendChild(infoDiv);
            }
        }

        // Ajouter cette fonction pour l'affichage des distances
        function formatDistance(km) {
            if (km < 1) {
                return Math.round(km * 1000) + ' m';
            } else if (km < 10) {
                return km.toFixed(1) + ' km';
            } else {
                return Math.round(km) + ' km';
            }
        }

        // Mettre à jour la fonction getCurrentPosition
        function getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Géolocalisation non supportée'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    position => {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            success: true
                        });
                    },
                    error => {
                        let message = 'Erreur de géolocalisation';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                message = "Géolocalisation refusée";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = "Position non disponible";
                                break;
                            case error.TIMEOUT:
                                message = "Délai dépassé";
                                break;
                        }
                        reject(new Error(message));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000
                    }
                );
            });
        }

        // Charger les produits pour un entrepôt
        async function loadProduits(entrepotId) {
            try {
                const response = await fetch(`../api/produits/available.php?entrepot_id=${entrepotId}`);
                produits = await response.json();
                displayProduits();
                document.getElementById('produits-container').style.display = 'block';
            } catch (error) {
                showAlert('Erreur lors du chargement des produits', 'error');
            }
        }

        // Afficher les produits
        function displayProduits() {
            const container = document.getElementById('produits-list');
            container.innerHTML = '';

            produits.forEach(produit => {
                const disponible = produit.quantite > 0;
                
                const stockBadge = produit.quantite == 0 ? 
                    '<span class="badge badge-rupture">Rupture</span>' :
                    produit.quantite <= produit.seuil_alerte ?
                    '<span class="badge badge-stock-faible">Stock faible</span>' :
                    '<span class="badge badge-stock-ok">En stock</span>';

                const obligatoireBadge = produit.obligatoire ? 
                    '<span class="badge badge-obligatoire">Obligatoire</span>' :
                    '<span class="badge badge-optionnel">Optionnel</span>';
                    
                const quantiteMinimaleInfo = produit.obligatoire && produit.quantite_minimale > 0 ?
                    `<small style="color: #ff5722; font-weight: bold;">Quantité minimale: ${produit.quantite_minimale}</small>` : '';

                container.innerHTML += `
                    <div class="produit-item ${!disponible ? 'indisponible' : ''}" data-produit-id="${produit.id}">
                        <div class="produit-header">
                            <span class="produit-nom">${produit.nom}</span>
                            <span class="produit-prix">${parseFloat(produit.prix_unitaire).toFixed(2)}€</span>
                        </div>
                        <div class="produit-info">
                            <div>
                                ${obligatoireBadge}
                                ${stockBadge}
                                <span style="margin-left: 0.5rem;">${produit.type}</span>
                                ${produit.quantite ? ` - Stock: ${produit.quantite} ${produit.unite}` : ''}
                                ${quantiteMinimaleInfo ? `<br>${quantiteMinimaleInfo}` : ''}
                            </div>
                            <div>
                                ${disponible ? `
                                    <label>Quantité: 
                                        <input type="number" class="quantite-input" 
                                               min="${produit.obligatoire && produit.quantite_minimale > 0 ? produit.quantite_minimale : 0}" 
                                               max="${produit.quantite || 999}" 
                                               step="1" value="0" 
                                               data-quantite-minimale="${produit.quantite_minimale || 0}"
                                               onchange="updateQuantite(${produit.id}, this.value)">
                                    </label>
                                ` : '<em>Indisponible</em>'}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Mettre à jour la quantité d'un produit
        function updateQuantite(produitId, quantite) {
            quantite = parseInt(quantite) || 0;
            
            const index = commandeActuelle.findIndex(item => item.produit_id == produitId);
            const produit = produits.find(p => p.id == produitId);
            
            // Vérifier la quantité minimale pour les produits obligatoires
            if (produit.obligatoire && produit.quantite_minimale > 0 && quantite > 0 && quantite < produit.quantite_minimale) {
                showAlert(`Le produit "${produit.nom}" nécessite une quantité minimale de ${produit.quantite_minimale}`, 'error');
                document.querySelector(`[data-produit-id="${produitId}"] .quantite-input`).value = produit.quantite_minimale;
                quantite = produit.quantite_minimale;
            }
            
            if (quantite > 0) {
                if (index >= 0) {
                    commandeActuelle[index].quantite = quantite;
                } else {
                    commandeActuelle.push({
                        produit_id: produitId,
                        nom: produit.nom,
                        prix_unitaire: produit.prix_unitaire,
                        quantite: quantite,
                        obligatoire: produit.obligatoire,
                        type: produit.type,
                        quantite_minimale: produit.quantite_minimale || 0
                    });
                }
                
                document.querySelector(`[data-produit-id="${produitId}"]`).classList.add('selected');
            } else {
                if (index >= 0) {
                    commandeActuelle.splice(index, 1);
                }
                
                document.querySelector(`[data-produit-id="${produitId}"]`).classList.remove('selected');
            }
            
            updateCommandeResume();
        }

        // Mettre à jour le résumé de commande
        function updateCommandeResume() {
            const resumeContainer = document.getElementById('commande-resume');
            const resumeItems = document.getElementById('resume-items');
            
            if (commandeActuelle.length === 0) {
                resumeContainer.style.display = 'none';
                return;
            }
            
            resumeContainer.style.display = 'block';
            
            let total = 0;
            let resumeHTML = '';
            let alertesQuantiteMinimale = [];
            
            commandeActuelle.forEach(item => {
                const sousTotal = item.prix_unitaire * item.quantite;
                total += sousTotal;
                
                // Vérifier les quantités minimales
                if (item.obligatoire && item.quantite_minimale > 0 && item.quantite < item.quantite_minimale) {
                    alertesQuantiteMinimale.push(`${item.nom}: ${item.quantite}/${item.quantite_minimale} minimum`);
                }
                
                resumeHTML += `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>${item.nom} x ${item.quantite}${item.quantite_minimale > 0 ? ` (min: ${item.quantite_minimale})` : ''}</span>
                        <span>${sousTotal.toFixed(2)}€</span>
                    </div>
                `;
            });
            
            resumeItems.innerHTML = resumeHTML;
            document.getElementById('total-commande').textContent = total.toFixed(2) + '€';
            
            // Afficher les alertes de quantité minimale
            const alerteContainer = document.getElementById('alerte-quantite-minimale');
            const detailsContainer = document.getElementById('details-quantite-minimale');
            
            if (alertesQuantiteMinimale.length > 0) {
                alerteContainer.style.display = 'block';
                detailsContainer.innerHTML = alertesQuantiteMinimale.map(alerte => 
                    `<div style="margin-top: 0.5rem; color: #d32f2f;">• ${alerte}</div>`
                ).join('');
            } else {
                alerteContainer.style.display = 'none';
            }
            
            totalCommande = total;
        }

        // Charger l'historique des commandes
        async function loadHistorique() {
            try {
                const response = await fetch('../api/commandes/list_by_user.php');
                const commandes = await response.json();
                
                const tbody = document.getElementById('historique-commandes');
                tbody.innerHTML = '';
                
                commandes.forEach(commande => {
                    const statutClass = `statut-${commande.statut}`;
                    const statutText = {
                        'en_attente': 'En attente',
                        'validee': 'Validée',
                        'livree': 'Livrée',
                        'annulee': 'Annulée'
                    }[commande.statut] || commande.statut;
                    
                    tbody.innerHTML += `
                        <tr>
                            <td>${new Date(commande.date_commande).toLocaleDateString('fr-FR')}</td>
                            <td>${commande.entrepot_nom || 'N/A'}</td>
                            <td>${commande.nb_produits}</td>
                            <td>${parseFloat(commande.total || 0).toFixed(2)}€</td>
                            <td><span class="statut-badge ${statutClass}">${statutText}</span></td>
                            <td title="${commande.produits_resume}">${commande.produits_resume ? commande.produits_resume.substring(0, 50) + '...' : 'N/A'}</td>
                        </tr>
                    `;
                });
            } catch (error) {
                showAlert('Erreur lors du chargement de l\'historique', 'error');
            }
        }

        // Gestion des onglets
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(`tab-${tab}`).classList.add('active');
            event.target.classList.add('active');
            
            if (tab === 'historique') {
                loadHistorique();
            }
        }

        // Réinitialiser la commande
        function resetCommande() {
            commandeActuelle = [];
            totalCommande = 0;
            
            document.querySelectorAll('.quantite-input').forEach(input => {
                input.value = 0;
            });
            
            document.querySelectorAll('.produit-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            updateCommandeResume();
        }

        // Soumettre la commande
        async function submitCommande(e) {
            e.preventDefault();
            
            const entrepotId = document.getElementById('entrepot-select').value;
            
            if (!entrepotId) {
                showAlert('Veuillez sélectionner un entrepôt', 'error');
                return;
            }
            
            if (commandeActuelle.length === 0) {
                showAlert('Veuillez sélectionner au moins un produit', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/commandes/create.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        entrepot_id: entrepotId,
                        produits: commandeActuelle
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(`Commande créée avec succès ! Total: ${result.total.toFixed(2)}€`, 'success');
                    resetCommande();
                    switchTab('historique');
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la création de la commande', 'error');
            }
        }

        // Afficher une alerte
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

        // Events
        document.getElementById('entrepot-select').addEventListener('change', function() {
            if (this.value) {
                loadProduits(this.value);
            } else {
                document.getElementById('produits-container').style.display = 'none';
            }
        });

        document.getElementById('commande-form').addEventListener('submit', submitCommande);
    </script>
</body>
</html>
