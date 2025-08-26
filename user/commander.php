<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commander - Client</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="commander.php" class="active">Commander</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content client">
            <h1>Commander</h1>
            <div class="section-card client">
                <h2>Trouvez votre food truck</h2>
                <p>Découvrez nos food trucks et passez votre commande en quelques clics.</p>
                <div id="geolocation-info" style="margin: 1rem 0; padding: 0.8rem; border-radius: 6px; background: #f8f9fa; border: 1px solid #dee2e6; display: none;">
                    <div id="geolocation-status"></div>
                </div>
            </div>
            <div id="camions-table-container"></div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script src="../js/admin/geolocation.js"></script>
    <script>
    const ClientCommandeManager = {
        data: { camions: [], userPosition: null, currentCamion: { id: null, nom: '', panier: [] } },
        config: {
            endpoints: {
                camionsProches: '../api/camions/plus_proche.php',
                camionsList: '../api/camions/list.php',
                menuCamion: '../api/menu/by_camion.php',
                passerCommande: '../api/commandes/client_add.php'
            },
            tableHeaders: ['Food Truck', 'Position', 'Distance', 'Action'],
            emptyMessage: 'Aucun food truck disponible pour le moment'
        },

        async init() {
            await this.loadCamions();
        },

        async loadCamions() {
            try {
                await this.detectPosition();
                const camions = await this.fetchCamions();
                this.displayCamions(camions);
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des food trucks', 'error');
                this.displayEmptyTable();
            }
        },

        async detectPosition() {
            const infoDiv = document.getElementById('geolocation-info');
            const statusDiv = document.getElementById('geolocation-status');
            infoDiv.style.display = 'block';
            statusDiv.innerHTML = '📍 Détection de votre position...';

            try {
                this.data.userPosition = await getCurrentPosition();
                statusDiv.innerHTML = 'Position détectée ! Tri par proximité activé.';
                statusDiv.style.color = '#4caf50';
                AdminCommon.utils.showAlert('📍 Position détectée avec succès !', 'success');
            } catch (error) {
                statusDiv.innerHTML = `⚠️ ${error.message} - Affichage par ordre alphabétique.`;
                statusDiv.style.color = '#ff9800';
                AdminCommon.utils.showAlert('Géolocalisation non disponible, affichage par ordre alphabétique', 'info');
            }
        },

        async fetchCamions() {
            const url = this.data.userPosition
                ? `${this.config.endpoints.camionsProches}?lat=${this.data.userPosition.latitude}&lng=${this.data.userPosition.longitude}`
                : this.config.endpoints.camionsList;

            let camions = await AdminCommon.utils.apiRequest(url);

            // Tri alphabétique si pas de position
            if (!this.data.userPosition && Array.isArray(camions)) {
                camions.sort((a, b) => (a.nom_camion || '').localeCompare(b.nom_camion || ''));
            }

            this.data.camions = camions;
            return camions;
        },

        displayCamions(camions) {
            AdminCommon.utils.createTable({
                containerId: 'camions-table-container',
                headers: this.config.tableHeaders,
                data: camions,
                rowBuilder: (camion, index) => {
                    const isRecommended = this.data.userPosition && index === 0 && camion.distance_km;
                    const distanceInfo = this.getDistanceInfo(camion.distance_km);
                    const recommendationBadge = isRecommended
                        ? ' <span style="background:#4caf50;color:white;padding:2px 6px;border-radius:12px;font-size:0.75rem;">🌟 Plus proche</span>' : '';
                    return [
                        `<strong>${camion.nom_camion || 'Food Truck #' + camion.id}</strong>${recommendationBadge}`,
                        camion.emplacement || 'Position non renseignée',
                        `<span style="padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.85rem; font-weight: 500; background: ${distanceInfo.style.color}; color: ${distanceInfo.style.textColor}; border: 1px solid ${distanceInfo.style.border};">${distanceInfo.style.icon} ${distanceInfo.text}</span>`,
                        `<button class="btn-primary" onclick="ClientCommandeManager.showCommandeModal(${camion.id}, '${(camion.nom_camion || 'Food Truck').replace(/'/g, "\\'")}')">Commander</button>`
                    ];
                },
                emptyMessage: this.config.emptyMessage
            });
        },

        displayEmptyTable() {
            AdminCommon.utils.createTable({
                containerId: 'camions-table-container',
                headers: this.config.tableHeaders,
                data: [],
                rowBuilder: () => [],
                emptyMessage: 'Erreur lors du chargement des food trucks'
            });
        },

        getDistanceInfo(km) {
            const styles = {
                proche: { color: '#d4edda', textColor: '#155724', border: '#c3e6cb', icon: '🟢' },
                moyen: { color: '#fff3cd', textColor: '#856404', border: '#ffeaa7', icon: '🟡' },
                loin: { color: '#f8d7da', textColor: '#721c24', border: '#f5c6cb', icon: '🔴' },
                inconnu: { color: '#e2e3e5', textColor: '#6c757d', border: '#d6d8db', icon: '⚫' }
            };
            if (!km) return { text: 'Non calculable', style: styles.inconnu };
            const text = AdminCommon.utils.formatDistance
                ? AdminCommon.utils.formatDistance(km)
                : (km >= 1 ? `${km.toFixed(1)} km` : `${Math.round(km * 1000)} m`);
            let style;
            if (km <= 5) style = styles.proche;
            else if (km <= 15) style = styles.moyen;
            else style = styles.loin;
            return { text, style };
        },

        async showCommandeModal(camionId, nomCamion) {
            try {
                const menu = await AdminCommon.utils.apiRequest(`${this.config.endpoints.menuCamion}?camion_id=${camionId}`);
                if (!Array.isArray(menu) || menu.length === 0) {
                    AdminCommon.utils.showAlert('Ce food truck n\'a pas encore de menu disponible', 'info');
                    return;
                }
                this.data.currentCamion = { id: camionId, nom: nomCamion, panier: [], menu: menu };
                AdminCommon.utils.createModal({
                    title: `Commander depuis ${nomCamion}`,
                    content: this.generateMenuHTML(menu),
                    size: 'large',
                    actions: [
                        { text: 'Annuler', type: 'secondary', onclick: 'AdminCommon.utils.closeModal()' },
                        { text: 'Valider ma commande', type: 'primary', onclick: 'ClientCommandeManager.submitCommande()' }
                    ]
                });
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement du menu', 'error');
            }
        },

        generateMenuHTML(menu) {
            const menuByCategory = this.groupMenuByCategory(menu);
            let html = `<div class="menu-selection">`;
            Object.entries(menuByCategory).forEach(([categorie, plats]) => {
                html += `
                    <div class="category-section" style="margin-bottom: 2rem;">
                        <h4 style="color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 0.5rem; margin-bottom: 1rem;">
                            ${this.getCategoryIcon(categorie)} ${this.getCategoryLabel(categorie)}
                        </h4>
                        <div class="plats-grid" style="display: grid; gap: 1rem;">
                            ${plats.map(plat => this.generatePlatHTML(plat)).join('')}
                        </div>
                    </div>
                `;
            });
            html += `
                <div id="commande-summary" style="display:none; margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                    <h5 style="color: #1976d2; margin-bottom: 1rem;">🛒 Récapitulatif de votre commande :</h5>
                    <div id="summary-items"></div>
                    <div style="border-top: 2px solid #1976d2; margin-top: 1rem; padding-top: 1rem; text-align: right;">
                        <strong style="font-size: 1.2rem;">Total : <span id="total-amount">0€</span></strong>
                    </div>
                </div>
            </div>`;
            return html;
        },

        groupMenuByCategory(menu) {
            return menu.reduce((groups, plat) => {
                const cat = plat.categorie || 'autre';
                if (!groups[cat]) groups[cat] = [];
                groups[cat].push(plat);
                return groups;
            }, {});
        },

        getCategoryIcon(categorie) {
            const icons = {
                'plat': '🍽️',
                'boisson': '🥤',
                'dessert': '🍰',
                'accompagnement': '🍟',
                'autre': '🍴'
            };
            return icons[categorie] || icons.autre;
        },

        getCategoryLabel(categorie) {
            const labels = {
                'plat': 'Plats principaux',
                'boisson': 'Boissons',
                'dessert': 'Desserts',
                'accompagnement': 'Accompagnements',
                'autre': 'Autres'
            };
            return labels[categorie] || 'Autres';
        },

        generatePlatHTML(plat) {
            return `
                <div class="menu-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; background: white; transition: box-shadow 0.2s;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1; margin-right: 1rem;">
                            <h6 style="margin: 0 0 0.5rem 0; color: #333; font-size: 1.1rem;">${plat.nom}</h6>
                            <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.9rem; line-height: 1.4;">
                                ${plat.description || 'Délicieux plat de notre chef'}
                            </p>
                            ${plat.ingredients ? `<small style="color: #999; font-style: italic;">Ingrédients: ${AdminCommon.utils.truncateText(plat.ingredients, 50)}</small>` : ''}
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.3rem; font-weight: bold; color: #1976d2; margin-bottom: 1rem;">
                                ${AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(plat.prix) : parseFloat(plat.prix).toFixed(2) + '€'}
                            </div>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; background: #f8f9fa; padding: 0.5rem; border-radius: 6px;">
                                <button type="button" onclick="ClientCommandeManager.updateQuantite(${plat.id}, -1)" class="btn-small" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">-</button>
                                <span id="qty-${plat.id}" style="min-width: 30px; text-align: center; font-weight: bold; font-size: 1.1rem;">0</span>
                                <button type="button" onclick="ClientCommandeManager.updateQuantite(${plat.id}, 1)" class="btn-small" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        updateQuantite(platId, delta) {
            const qtySpan = document.getElementById(`qty-${platId}`);
            const currentQty = parseInt(qtySpan.textContent) || 0;
            const newQty = Math.max(0, currentQty + delta);
            qtySpan.textContent = newQty;
            const existingIndex = this.data.currentCamion.panier.findIndex(item => item.id === platId);
            if (newQty > 0) {
                const platInfo = this.data.currentCamion.menu.find(p => p.id === platId);
                if (existingIndex >= 0) {
                    this.data.currentCamion.panier[existingIndex].quantite = newQty;
                    this.data.currentCamion.panier[existingIndex].total = newQty * platInfo.prix;
                } else {
                    this.data.currentCamion.panier.push({
                        id: platId,
                        nom: platInfo.nom,
                        prix: parseFloat(platInfo.prix),
                        quantite: newQty,
                        total: newQty * parseFloat(platInfo.prix)
                    });
                }
            } else if (existingIndex >= 0) {
                this.data.currentCamion.panier.splice(existingIndex, 1);
            }
            this.updateCommandeSummary();
        },

        updateCommandeSummary() {
            const summaryDiv = document.getElementById('commande-summary');
            const itemsDiv = document.getElementById('summary-items');
            const totalSpan = document.getElementById('total-amount');
            if (this.data.currentCamion.panier.length === 0) {
                summaryDiv.style.display = 'none';
                return;
            }
            summaryDiv.style.display = 'block';
            let total = 0;
            itemsDiv.innerHTML = this.data.currentCamion.panier.map(item => {
                total += item.total;
                return `
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        <span><strong>${item.nom}</strong> × ${item.quantite}</span>
                        <span style="color: #1976d2; font-weight: bold;">${AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(item.total) : item.total.toFixed(2) + '€'}</span>
                    </div>
                `;
            }).join('');
            totalSpan.textContent = AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(total) : `${total.toFixed(2)}€`;
        },

        async submitCommande() {
            if (this.data.currentCamion.panier.length === 0) {
                AdminCommon.utils.showAlert('Veuillez sélectionner au moins un plat', 'error');
                return;
            }
            try {
                const result = await AdminCommon.utils.apiRequest(this.config.endpoints.passerCommande, {
                    method: 'POST',
                    data: {
                        camion_id: this.data.currentCamion.id,
                        plats: this.data.currentCamion.panier
                    }
                });
                if (result.success) {
                    AdminCommon.utils.closeModal();
                    AdminCommon.utils.showAlert(`Commande créée ! Redirection vers le paiement...`, 'success');
                    setTimeout(() => {
                        window.location.href = `paiement.php?commande_id=${result.commande_id}&total=${result.total}`;
                    }, 1500);
                } else {
                    AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur réseau lors de la commande', 'error');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        ClientCommandeManager.init();
    });
    </script>
</body>
</html>