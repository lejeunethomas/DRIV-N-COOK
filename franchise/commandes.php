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
    <style>
        .stock-alert-overlay,

        .stock-warning-absolute,

        .floating-stock-alert { 
            display: none !important; 
        }

        .badge { 
            position: static !important; 
            display: inline-block !important; 
            margin: 0 0.25rem 0 0 !important; 
            vertical-align: middle !important; 
        }

        [style*="position: absolute"]:not(.modal):not(.dropdown) { 
            position: static !important; 
        }

        .produit-item { 
            position: relative !important; 
            overflow: visible !important; 
        }

        .produit-info { 
            display: flex !important; 
            flex-direction: column !important; 
            gap: 0.5rem !important; 
        }

        .badges-container { 
            display: flex !important; 
            flex-wrap: wrap !important; 
            gap: 0.5rem !important; 
            align-items: center !important; 
            margin-bottom: 0.5rem !important; 
        }
    </style>
</head>
<body data-role="franchise">
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
                <button class="tab-btn franchise active" onclick="CommandeUtils.switchTab('nouvelle')">Nouvelle commande</button>
                <button class="tab-btn franchise" onclick="CommandeUtils.switchTab('historique')">Historique</button>
            </div>

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
                                    <button type="button" class="btn-secondary" onclick="CommandeManager.reset()">Réinitialiser</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-historique" class="tab-content">
                <h2>Historique des commandes</h2>
                <div id="historique-table-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/geolocation.js"></script>
    <script>
    // ==== UTILS ====
    const CommandeUtils = {
        showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info';
            alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            setTimeout(() => alertContainer.innerHTML = '', 5000);
        },
        switchTab(tab) {
            document.querySelectorAll('.tab-content, .tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');
            event.target.classList.add('active');
            if (tab === 'historique') CommandeManager.loadHistorique();
        },
        getStatutLabel(statut) {
            return CommandeManager.config.statutLabels[statut] || statut;
        },
        truncateText(text, maxLength = 50) {
            if (!text) return 'N/A';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        },
        formatDistance(km) {
            return km >= 1 ? `${km.toFixed(1)} km` : `${Math.round(km * 1000)} m`;
        },
        getDeliveryTimeEstimate(distanceKm) {
            if (distanceKm <= 10) return { text: "Même jour", class: "delivery-today" };
            if (distanceKm <= 50) return { text: "24h", class: "delivery-next" };
            if (distanceKm <= 150) return { text: "48h", class: "delivery-week" };
            return { text: "3-5 jours", class: "delivery-long" };
        },
        createTable({ containerId, headers, data, rowBuilder, emptyMessage }) {
            const container = document.getElementById(containerId);
            if (!data || data.length === 0) {
                container.innerHTML = `<p style="text-align: center; color: #666; padding: 2rem;">${emptyMessage}</p>`;
                return;
            }
            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${data.map(item => {
                        const cells = rowBuilder(item);
                        return `<tr>${cells.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
                    }).join('')}
                </tbody>
            `;
            container.innerHTML = '';
            container.appendChild(table);
        },
        removeFloatingAlerts() {
            const floatingElements = document.querySelectorAll('[style*="position: absolute"], [style*="position:absolute"]');
            floatingElements.forEach(element => {
                if (!element.classList.contains('modal') && 
                    !element.classList.contains('dropdown') && 
                    !element.closest('.modal') && 
                    !element.closest('.dropdown') &&
                    (element.textContent.includes('stock') || element.textContent.includes('Stock'))) {
                    element.remove();
                }
            });
        }
    };

    // ==== MANAGER PRINCIPAL ====
    const CommandeManager = {
        data: { entrepots: [], produits: [], commandeActuelle: [], total: 0 },
        config: {
            endpoints: {
                entrepots: '../api/entrepots/list.php',
                entrepotsProches: '../api/entrepots/plus_proches.php',
                produits: '../api/produits/available.php',
                create: '../api/commandes/create.php',
                historique: '../api/commandes/list_by_user.php'
            },
            tables: {
                historique: {
                    headers: ['Date', 'Entrepôt', 'Nb produits', 'Total', 'Statut', 'Produits'],
                    rowBuilder: (commande) => [
                        new Date(commande.date_commande).toLocaleDateString('fr-FR'),
                        commande.entrepot_nom || 'N/A',
                        commande.nb_produits,
                        `${parseFloat(commande.total || 0).toFixed(2)}€`,
                        `<span class="statut-badge statut-${commande.statut}">${CommandeUtils.getStatutLabel(commande.statut)}</span>`,
                        `<span title="${commande.produits_resume}">${CommandeUtils.truncateText(commande.produits_resume, 50)}</span>`
                    ]
                }
            },
            statutLabels: {
                'en_attente': 'En attente',
                'validee': 'Validée', 
                'livree': 'Livrée',
                'annulee': 'Annulée'
            },
            badges: {
                stock: {
                    rupture: '<span class="badge" style="background: #f44336; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.5rem;">Rupture</span>',
                    faible: '<span class="badge" style="background: #ff9800; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.5rem;">Stock faible</span>',
                    ok: '<span class="badge" style="background: #4caf50; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.5rem;">En stock</span>'
                },
                obligatoire: {
                    oui: '<span class="badge" style="background: #e64a19; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.5rem;">Obligatoire</span>',
                    non: '<span class="badge" style="background: #666; color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.5rem;">Optionnel</span>'
                }
            }
        },

        async init() {
            await Promise.all([this.loadEntrepots(), this.loadHistorique()]);
            document.getElementById('entrepot-select').addEventListener('change', (e) => {
                e.target.value ? this.loadProduits(e.target.value) : 
                    document.getElementById('produits-container').style.display = 'none';
            });
            document.getElementById('commande-form').addEventListener('submit', this.submit.bind(this));
            setTimeout(CommandeUtils.removeFloatingAlerts, 500);
        },

        async loadEntrepots() {
            try {
                let userPosition = null;
                try {
                    userPosition = await getCurrentPosition();
                    CommandeUtils.showAlert('📍 Position détectée, tri par proximité activé', 'success');
                } catch (error) {
                    CommandeUtils.showAlert('Géolocalisation non disponible, affichage par ordre alphabétique', 'info');
                }
                const url = userPosition ? 
                    `${this.config.endpoints.entrepotsProches}?lat=${userPosition.latitude}&lng=${userPosition.longitude}` :
                    this.config.endpoints.entrepots;
                const response = await fetch(url);
                this.data.entrepots = await response.json();
                this.displayEntrepots(userPosition !== null);
            } catch (error) {
                CommandeUtils.showAlert('Erreur lors du chargement des entrepôts', 'error');
            }
        },

        displayEntrepots(hasGeolocation = false) {
            const select = document.getElementById('entrepot-select');
            select.innerHTML = '<option value="">Sélectionner un entrepôt</option>';
            this.data.entrepots.forEach((entrepot, index) => {
                const distanceText = entrepot.distance_km ? 
                    ` (${CommandeUtils.formatDistance(entrepot.distance_km)})` : '';
                const recommendationBadge = index === 0 && entrepot.distance_km ? ' 🌟' : '';
                select.innerHTML += `
                    <option value="${entrepot.id}">
                        ${entrepot.nom} - ${entrepot.ville}${distanceText}${recommendationBadge}
                    </option>
                `;
            });
            // Affichage des infos de proximité
            if (hasGeolocation && this.data.entrepots.length > 0 && this.data.entrepots[0].distance_km) {
                const infoContainer = select.parentNode;
                const oldInfo = infoContainer.querySelector('.proximity-info');
                if (oldInfo) oldInfo.remove();
                const deliveryInfo = CommandeUtils.getDeliveryTimeEstimate(this.data.entrepots[0].distance_km);
                const infoDiv = document.createElement('div');
                infoDiv.className = 'proximity-info';
                infoDiv.style.cssText = `
                    background: #e8f5e8; border: 1px solid #4caf50; border-radius: 6px; 
                    padding: 0.8rem; margin-top: 0.5rem; font-size: 0.9rem;
                `;
                infoDiv.innerHTML = `
                    📍 <strong>Entrepôts triés par proximité</strong><br>
                    <small>Le plus proche est à ${CommandeUtils.formatDistance(this.data.entrepots[0].distance_km)} 
                    (livraison estimée : ${deliveryInfo.text})</small>
                `;
                infoContainer.appendChild(infoDiv);
            }
        },

        async loadProduits(entrepotId) {
            try {
                const response = await fetch(`${this.config.endpoints.produits}?entrepot_id=${entrepotId}`);
                this.data.produits = await response.json();
                this.displayProduits();
                document.getElementById('produits-container').style.display = 'block';
            } catch (error) {
                CommandeUtils.showAlert('Erreur lors du chargement des produits', 'error');
            }
        },

        displayProduits() {
            const container = document.getElementById('produits-list');
            container.innerHTML = '';
            this.data.produits.forEach(produit => {
                const disponible = produit.quantite > 0;
                const stockBadge = this.config.badges.stock[produit.quantite == 0 ? 'rupture' : (produit.quantite <= produit.seuil_alerte ? 'faible' : 'ok')];
                const obligatoireBadge = this.config.badges.obligatoire[produit.obligatoire ? 'oui' : 'non'];
                const quantiteMinimaleInfo = produit.obligatoire && produit.quantite_minimale > 0 ?
                    `<small style="color: #ff5722; font-weight: bold;">Quantité minimale: ${produit.quantite_minimale}</small>` : '';
                container.innerHTML += `
                    <div class="produit-item ${!disponible ? 'indisponible' : ''}" data-produit-id="${produit.id}" 
                         style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-weight: bold; font-size: 1.1rem; color: #333;">${produit.nom}</span>
                            <span style="font-weight: bold; color: #e64a19; font-size: 1.1rem;">${parseFloat(produit.prix_unitaire).toFixed(2)}€</span>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <div style="margin-bottom: 0.5rem;">
                                ${obligatoireBadge}
                                ${stockBadge}
                                <span style="color: #666; font-size: 0.9rem;">${produit.type}</span>
                            </div>
                            ${produit.quantite ? `
                                <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">
                                    Stock disponible: <strong>${produit.quantite} ${produit.unite}</strong>
                                </div>
                            ` : ''}
                            ${quantiteMinimaleInfo}
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid #eee;">
                            <span style="font-weight: 500; color: #555;">Quantité à commander :</span>
                            ${disponible ? `
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="number" class="quantite-input" 
                                           style="width: 80px; padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; text-align: center;"
                                           min="${produit.obligatoire && produit.quantite_minimale > 0 ? produit.quantite_minimale : 0}" 
                                           max="${produit.quantite || 999}" 
                                           step="1" value="0" 
                                           data-quantite-minimale="${produit.quantite_minimale || 0}"
                                           onchange="CommandeManager.updateQuantite(${produit.id}, this.value)">
                                    <span style="color: #666; font-size: 0.9rem;">${produit.unite || 'unités'}</span>
                                </div>
                            ` : '<em style="color: #999; font-style: italic;">Indisponible</em>'}
                        </div>
                    </div>
                `;
            });
            setTimeout(CommandeUtils.removeFloatingAlerts, 100);
        },

        updateQuantite(produitId, quantite) {
            quantite = parseInt(quantite) || 0;
            const index = this.data.commandeActuelle.findIndex(item => item.produit_id == produitId);
            const produit = this.data.produits.find(p => p.id == produitId);
            if (produit.obligatoire && produit.quantite_minimale > 0 && quantite > 0 && quantite < produit.quantite_minimale) {
                CommandeUtils.showAlert(`Le produit "${produit.nom}" nécessite une quantité minimale de ${produit.quantite_minimale}`, 'error');
                document.querySelector(`[data-produit-id="${produitId}"] .quantite-input`).value = produit.quantite_minimale;
                quantite = produit.quantite_minimale;
            }
            if (quantite > 0) {
                if (index >= 0) {
                    this.data.commandeActuelle[index].quantite = quantite;
                } else {
                    this.data.commandeActuelle.push({
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
                if (index >= 0) this.data.commandeActuelle.splice(index, 1);
                document.querySelector(`[data-produit-id="${produitId}"]`).classList.remove('selected');
            }
            this.updateResume();
        },

        updateResume() {
            const resumeContainer = document.getElementById('commande-resume');
            const resumeItems = document.getElementById('resume-items');
            if (this.data.commandeActuelle.length === 0) {
                resumeContainer.style.display = 'none';
                return;
            }
            resumeContainer.style.display = 'block';
            let total = 0;
            let resumeHTML = '';
            let alertesQuantiteMinimale = [];
            this.data.commandeActuelle.forEach(item => {
                const sousTotal = item.prix_unitaire * item.quantite;
                total += sousTotal;
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
            // Gestion des alertes
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
            this.data.total = total;
        },

        async loadHistorique() {
            try {
                const response = await fetch(this.config.endpoints.historique);
                const commandes = await response.json();
                CommandeUtils.createTable({
                    containerId: 'historique-table-container',
                    headers: this.config.tables.historique.headers,
                    data: commandes,
                    rowBuilder: this.config.tables.historique.rowBuilder,
                    emptyMessage: 'Aucune commande trouvée'
                });
            } catch (error) {
                CommandeUtils.showAlert('Erreur lors du chargement de l\'historique', 'error');
            }
        },

        reset() {
            this.data.commandeActuelle = [];
            this.data.total = 0;
            document.querySelectorAll('.quantite-input').forEach(input => input.value = 0);
            document.querySelectorAll('.produit-item').forEach(item => item.classList.remove('selected'));
            this.updateResume();
        },

        async submit(e) {
            e.preventDefault();
            const entrepotId = document.getElementById('entrepot-select').value;
            if (!entrepotId) {
                CommandeUtils.showAlert('Veuillez sélectionner un entrepôt', 'error');
                return;
            }
            if (this.data.commandeActuelle.length === 0) {
                CommandeUtils.showAlert('Veuillez sélectionner au moins un produit', 'error');
                return;
            }
            try {
                const response = await fetch(this.config.endpoints.create, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        entrepot_id: entrepotId,
                        produits: this.data.commandeActuelle
                    })
                });
                const result = await response.json();
                if (result.success) {
                    const total = Number(result.total);
                    CommandeUtils.showAlert(
                        `Commande créée avec succès ! Total: ${isNaN(total) ? '-' : total.toFixed(2)}€`, 
                        'success'
                    );
                    this.reset();
                    CommandeUtils.switchTab('historique');
                } else {
                    CommandeUtils.showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                CommandeUtils.showAlert('Erreur réseau lors de la création de la commande', 'error');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => CommandeManager.init());
    </script>
</body>
</html>
