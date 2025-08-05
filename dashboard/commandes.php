<?php
require_once '../includes/auth.php';
require_admin(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des commandes - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php" class="active">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content admin">
            <h1 class="admin">Gestion des commandes</h1>
            
            <div id="alert-container"></div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('en_attente')">
                    En attente <span class="badge" id="badge-attente" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="switchTab('validees')">
                    Validées <span class="badge" id="badge-validees" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="switchTab('toutes')">Toutes les commandes</button>
            </div>

            <!-- Onglet Commandes en attente -->
            <div id="tab-en_attente" class="tab-content active">
                <h2>Commandes en attente de validation</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Franchisé</th>
                            <th>Entrepôt</th>
                            <th>Nb produits</th>
                            <th>Total</th>
                            <th>Urgence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="commandes-attente">
                    </tbody>
                </table>
            </div>

            <!-- Onglet Commandes validées -->
            <div id="tab-validees" class="tab-content">
                <h2>Commandes validées - En préparation</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date commande</th>
                            <th>Franchisé</th>
                            <th>Entrepôt</th>
                            <th>Total</th>
                            <th>Date livraison</th>
                            <th>Statut</th>
                            <th>Estimation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="commandes-validees">
                    </tbody>
                </table>
            </div>

            <!-- Onglet Toutes les commandes -->
            <div id="tab-toutes" class="tab-content">
                <h2>Historique complet des commandes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Franchisé</th>
                            <th>Entrepôt</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Validé par</th>
                            <th>Distance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="commandes-toutes">
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        let commandesData = {
            en_attente: [],
            validees: [],
            toutes: []
        };

        // Chargement initial
        document.addEventListener('DOMContentLoaded', function() {
            loadCommandes('en_attente');
            updateBadges();
        });

        // Charger les commandes par statut
        async function loadCommandes(statut) {
            try {
                const url = statut === 'toutes' ? '../api/commandes/list_all.php' : `../api/commandes/list_all.php?statut=${statut}`;
                const response = await fetch(url);
                const commandes = await response.json();
                
                commandesData[statut] = commandes;
                displayCommandes(statut);
                
                if (statut === 'en_attente') {
                    updateBadges();
                }
            } catch (error) {
                showAlert('Erreur lors du chargement des commandes', 'error');
                console.error('Erreur:', error);
            }
        }

        // Afficher les commandes
        function displayCommandes(statut) {
            const tbody = document.getElementById(`commandes-${statut === 'toutes' ? 'toutes' : statut}`);
            tbody.innerHTML = '';

            const commandes = commandesData[statut];
            
            commandes.forEach(commande => {
                const dateCommande = new Date(commande.date_commande).toLocaleDateString('fr-FR');
                const dateLivraison = commande.date_livraison_prevue ? 
                    new Date(commande.date_livraison_prevue).toLocaleDateString('fr-FR') : '-';
                
                const statutClass = `statut-${commande.statut}`;
                const statutText = {
                    'en_attente': 'En attente',
                    'validee': 'Validée',
                    'en_preparation': 'En préparation',
                    'livree': 'Livrée',
                    'annulee': 'Annulée'
                }[commande.statut] || commande.statut;
                
                // Calculer l'urgence (commandes de plus de 2 jours)
                const joursDiff = Math.floor((new Date() - new Date(commande.date_commande)) / (1000 * 60 * 60 * 24));
                const urgenceClass = (joursDiff > 2 && commande.statut === 'en_attente') || 
                                   commande.urgence_livraison === 'urgente' ? 'commande-urgente' : '';
                
                // Affichage de l'urgence avec distance
                let urgenceText = joursDiff + ' jour(s)';
                if (commande.distance_km) {
                    const distanceInfo = `📍 ${commande.distance_km}km`;
                    const tempsInfo = `⏱️ ${commande.temps_livraison_estime}`;
                    
                    if (commande.urgence_livraison === 'urgente') {
                        urgenceText = `🚨 ${joursDiff} jours<br><small style="color: #d32f2f;">${distanceInfo} - ${tempsInfo}</small>`;
                    } else if (commande.urgence_livraison === 'attention') {
                        urgenceText = `⚠️ ${joursDiff} jours<br><small style="color: #f57c00;">${distanceInfo} - ${tempsInfo}</small>`;
                    } else {
                        urgenceText = `${joursDiff} jour(s)<br><small style="color: #666;">${distanceInfo} - ${tempsInfo}</small>`;
                    }
                } else if (joursDiff > 2 && commande.statut === 'en_attente') {
                    urgenceText = `🚨 ${joursDiff} jours<br><small style="color: #d32f2f;">Distance non calculable</small>`;
                }
                
                let actionsHTML = '';
                if (commande.statut === 'en_attente') {
                    actionsHTML = `
                        <button class="btn-action success" onclick="validerCommande(${commande.id})">Valider</button>
                        <button class="btn-action danger" onclick="annulerCommande(${commande.id})">Annuler</button>
                        <button class="btn-action" onclick="voirDetails(${commande.id})">Détails</button>
                    `;
                } else if (commande.statut === 'validee') {
                    actionsHTML = `
                        <button class="btn-action warning" onclick="marquerLivree(${commande.id})">Marquer livrée</button>
                        <button class="btn-action" onclick="voirDetails(${commande.id})">Détails</button>
                    `;
                } else {
                    actionsHTML = `
                        <button class="btn-action" onclick="voirDetails(${commande.id})">Détails</button>
                    `;
                }

                if (statut === 'en_attente') {
                    tbody.innerHTML += `
                        <tr class="${urgenceClass}">
                            <td>${dateCommande}</td>
                            <td><strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong></td>
                            <td>${commande.entrepot_nom}</td>
                            <td>${commande.nb_produits} produit(s)</td>
                            <td>${parseFloat(commande.total || 0).toFixed(2)}€</td>
                            <td>${urgenceText}</td>
                            <td>${actionsHTML}</td>
                        </tr>
                    `;
                } else if (statut === 'validees') {
                    tbody.innerHTML += `
                        <tr>
                            <td>${dateCommande}</td>
                            <td><strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong></td>
                            <td>${commande.entrepot_nom}</td>
                            <td>${parseFloat(commande.total || 0).toFixed(2)}€</td>
                            <td>${dateLivraison}</td>
                            <td><span class="statut-badge ${statutClass}">${statutText}</span></td>
                            <td>${commande.distance_km ? 
                                `<small>📍 ${commande.distance_km}km<br>⏱️ ${commande.temps_livraison_estime}</small>` : 
                                '<small style="color: #999;">Non calculable</small>'
                            }</td>
                            <td>${actionsHTML}</td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML += `
                        <tr>
                            <td>${dateCommande}</td>
                            <td><strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong></td>
                            <td>${commande.entrepot_nom}</td>
                            <td>${parseFloat(commande.total || 0).toFixed(2)}€</td>
                            <td><span class="statut-badge ${statutClass}">${statutText}</span></td>
                            <td>${commande.admin_nom ? commande.admin_nom + ' ' + commande.admin_prenom : '-'}</td>
                            <td>${commande.distance_km ? 
                                `<small>📍 ${commande.distance_km}km</small>` : 
                                '<small style="color: #999;">-</small>'
                            }</td>
                            <td>${actionsHTML}</td>
                        </tr>
                    `;
                }
            });
        }

        // Mettre à jour les badges
        async function updateBadges() {
            try {
                const attenteResponse = await fetch('../api/commandes/list_all.php?statut=en_attente');
                const attenteCommandes = await attenteResponse.json();
                
                const valideesResponse = await fetch('../api/commandes/list_all.php?statut=validee');
                const valideesCommandes = await valideesResponse.json();
                
                const badgeAttente = document.getElementById('badge-attente');
                const badgeValidees = document.getElementById('badge-validees');
                
                if (attenteCommandes.length > 0) {
                    badgeAttente.textContent = attenteCommandes.length;
                    badgeAttente.style.display = '';
                } else {
                    badgeAttente.style.display = 'none';
                }
                
                if (valideesCommandes.length > 0) {
                    badgeValidees.textContent = valideesCommandes.length;
                    badgeValidees.style.display = '';
                } else {
                    badgeValidees.style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur lors de la mise à jour des badges:', error);
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
            
            if (!commandesData[tab] || commandesData[tab].length === 0) {
                loadCommandes(tab);
            }
        }

        // Voir les détails d'une commande
        async function voirDetails(commandeId) {
            try {
                const response = await fetch(`../api/commandes/details.php?id=${commandeId}`);
                const commande = await response.json();
                
                if (commande.error) {
                    showAlert('Erreur: ' + commande.error, 'error');
                    return;
                }
                
                showDetailsModal(commande);
            } catch (error) {
                showAlert('Erreur lors du chargement des détails', 'error');
            }
        }

        // Modal des détails
        function showDetailsModal(commande) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            
            // Calcul de l'estimation si les coordonnées sont disponibles
            let estimationHTML = '';
            if (commande.distance_km) {
                const urgenceClass = commande.urgence_livraison === 'urgente' ? 'stock-warning' : 
                                   commande.urgence_livraison === 'attention' ? 'stock-warning' : 'stock-ok';
                
                estimationHTML = `
                    <div class="detail-section">
                        <h4>Informations de livraison</h4>
                        <div class="form-row">
                            <div><strong>Distance:</strong> ${commande.distance_km} km</div>
                            <div><strong>Temps estimé:</strong> <span class="${urgenceClass}">${commande.temps_livraison_estime}</span></div>
                        </div>
                        ${commande.urgence_livraison === 'urgente' ? 
                            '<div style="background: #fff3e0; border: 1px solid #ff9800; border-radius: 4px; padding: 0.5rem; margin-top: 0.5rem;"><strong>Livraison urgente recommandée</strong></div>' : 
                            ''
                        }
                    </div>
                `;
            }
            
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Détails de la commande #${commande.id}</h3>
                    
                    <div class="detail-section">
                        <h4>Informations générales</h4>
                        <div class="form-row">
                            <div><strong>Date de commande:</strong> ${new Date(commande.date_commande).toLocaleDateString('fr-FR')}</div>
                            <div><strong>Statut:</strong> <span class="statut-badge statut-${commande.statut}">${getStatutText(commande.statut)}</span></div>
                        </div>
                        <div class="form-row">
                            <div><strong>Franchisé:</strong> ${commande.franchise_nom} ${commande.franchise_prenom}</div>
                            <div><strong>Email:</strong> ${commande.franchise_email}</div>
                        </div>
                        <div class="form-row">
                            <div><strong>Entrepôt:</strong> ${commande.entrepot_nom}</div>
                            <div><strong>Adresse:</strong> ${commande.entrepot_adresse}, ${commande.entrepot_ville}</div>
                        </div>
                        ${commande.date_livraison_prevue ? `
                            <div><strong>Date de livraison prévue:</strong> ${new Date(commande.date_livraison_prevue).toLocaleDateString('fr-FR')}</div>
                        ` : ''}
                        ${commande.admin_nom ? `
                            <div><strong>Validé par:</strong> ${commande.admin_nom} ${commande.admin_prenom}</div>
                        ` : ''}
                    </div>
                    
                    ${estimationHTML}
                    
                    <div class="detail-section">
                        <h4>Produits commandés</h4>
                        ${commande.details.map(detail => {
                            const stockSuffisant = detail.stock_disponible >= detail.quantite;
                            const stockClass = stockSuffisant ? 'stock-ok' : 'stock-warning';
                            const stockText = stockSuffisant ? 
                                `Stock: ${detail.stock_disponible} ${detail.stock_unite}` :
                                `Stock insuffisant: ${detail.stock_disponible}/${detail.quantite} ${detail.stock_unite}`;
                            
                            return `
                                <div class="produit-detail">
                                    <div>
                                        <strong>${detail.produit_nom}</strong> (${detail.produit_type})<br>
                                        <small class="${stockClass}">${stockText}</small>
                                    </div>
                                    <div style="text-align: right;">
                                        ${detail.quantite} × ${parseFloat(detail.prix_unitaire).toFixed(2)}€<br>
                                        <strong>${parseFloat(detail.prix_total).toFixed(2)}€</strong>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                        <div style="text-align: right; padding-top: 1rem; border-top: 2px solid #1976d2; margin-top: 1rem;">
                            <strong style="font-size: 1.2rem;">Total: ${parseFloat(commande.total).toFixed(2)}€</strong>
                        </div>
                    </div>
                    
                    ${commande.commentaire_admin ? `
                        <div class="detail-section">
                            <h4>Commentaires administrateur</h4>
                            <p>${commande.commentaire_admin}</p>
                        </div>
                    ` : ''}
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        // Valider une commande
        function validerCommande(commandeId) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Valider la commande #${commandeId}</h3>
                    <form id="validation-form">
                        <div class="form-group">
                            <label for="date_livraison">Date de livraison prévue *</label>
                            <input type="date" id="date_livraison" name="date_livraison" required min="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="form-group">
                            <label for="commentaire">Commentaire (optionnel)</label>
                            <textarea id="commentaire" name="commentaire" placeholder="Instructions particulières, notes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="reserver_stocks" name="reserver_stocks" checked>
                                Réserver les stocks maintenant (recommandé)
                            </label>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Valider la commande</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('validation-form').onsubmit = async function(e) {
                e.preventDefault();
                await submitValidation(commandeId, 'valider');
            };
        }

        // Annuler une commande
        function annulerCommande(commandeId) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Annuler la commande #${commandeId}</h3>
                    <div class="form-group">
                        <label for="raison_annulation">Raison de l'annulation *</label>
                        <textarea id="raison_annulation" required placeholder="Expliquez pourquoi cette commande est annulée..."></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Retour</button>
                        <button type="button" class="btn-action danger" onclick="confirmAnnulation(${commandeId})">Confirmer l'annulation</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        // Confirmer l'annulation
        async function confirmAnnulation(commandeId) {
            const raison = document.getElementById('raison_annulation').value;
            if (!raison.trim()) {
                alert('Veuillez indiquer une raison pour l\'annulation');
                return;
            }
            
            await submitValidation(commandeId, 'annuler', { commentaire: raison });
        }

        // Marquer comme livrée
        async function marquerLivree(commandeId) {
            if (!confirm('Confirmer que cette commande a été livrée ? Les stocks seront automatiquement mis à jour.')) {
                return;
            }
            
            await submitValidation(commandeId, 'livrer');
        }

        // Soumettre une action de validation
        async function submitValidation(commandeId, action, extraData = {}) {
            try {
                const formData = action === 'valider' ? {
                    commande_id: commandeId,
                    action: action,
                    date_livraison: document.getElementById('date_livraison')?.value,
                    commentaire: document.getElementById('commentaire')?.value,
                    reserver_stocks: document.getElementById('reserver_stocks')?.checked,
                    ...extraData
                } : {
                    commande_id: commandeId,
                    action: action,
                    ...extraData
                };
                
                const response = await fetch('../api/commandes/validate.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal();
                    loadCommandes('en_attente');
                    loadCommandes('validees');
                    updateBadges();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la validation', 'error');
                console.error('Erreur:', error);
            }
        }

        // Fonctions utilitaires
        function getStatutText(statut) {
            const texts = {
                'en_attente': 'En attente',
                'validee': 'Validée',
                'en_preparation': 'En préparation',
                'livree': 'Livrée',
                'annulee': 'Annulée'
            };
            return texts[statut] || statut;
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

        // Fermer le modal en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });

        // Actualisation automatique des commandes en attente toutes les 30 secondes
        setInterval(() => {
            if (document.getElementById('tab-en_attente').classList.contains('active')) {
                loadCommandes('en_attente');
                updateBadges();
            }
        }, 30000);
    </script>
</body>
</html>