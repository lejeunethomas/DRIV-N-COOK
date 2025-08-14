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
<body data-role="admin">
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
            
            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('en_attente', CommandeAdminManager.loadEnAttente.bind(CommandeAdminManager))">
                    En attente <span class="badge" id="badge-attente" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('validees', CommandeAdminManager.loadValidees.bind(CommandeAdminManager))">
                    Validées <span class="badge" id="badge-validees" style="display:none;">0</span>
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('toutes', CommandeAdminManager.loadToutes.bind(CommandeAdminManager))">
                    Toutes les commandes
                </button>
            </div>

            <div id="tab-en_attente" class="tab-content active">
                <h2>Commandes en attente de validation</h2>
                <div id="table-en_attente-container"></div>
            </div>

            <div id="tab-validees" class="tab-content">
                <h2>Commandes validées - En préparation</h2>
                <div id="table-validees-container"></div>
            </div>

            <div id="tab-toutes" class="tab-content">
                <h2>Historique complet des commandes</h2>
                <div id="table-toutes-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script>
    const CommandeAdminManager = {
        data: { en_attente: [], validees: [], toutes: [] },
        config: {
            endpoints: {
                list: '../api/commandes/list_all.php',
                details: '../api/commandes/details.php',
                validate: '../api/commandes/validate.php'
            },
            tables: {
                en_attente: {
                    headers: ['Date', 'Franchisé', 'Entrepôt', 'Nb produits', 'Total', 'Urgence', 'Actions'],
                    rowBuilder: (commande) => {
                        const urgenceData = CommandeAdminManager.calculateUrgence(commande);
                        const rowClass = urgenceData.isUrgent ? 'commande-urgente' : '';
                        return [
                            CommandeAdminManager.formatDate(commande.date_commande),
                            `<strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong>`,
                            commande.entrepot_nom,
                            `${commande.nb_produits} produit(s)`,
                            `<strong>${CommandeAdminManager.formatPrice(commande.total)}</strong>`,
                            urgenceData.display,
                            `<button class="btn-action success" onclick="CommandeAdminManager.validerCommande(${commande.id})">Valider</button>
                             <button class="btn-action danger" onclick="CommandeAdminManager.annulerCommande(${commande.id})">Annuler</button>
                             <button class="btn-action" onclick="CommandeAdminManager.voirDetails(${commande.id})">Détails</button>`,
                            rowClass
                        ];
                    }
                },
                validees: {
                    headers: ['Date commande', 'Franchisé', 'Entrepôt', 'Total', 'Date livraison', 'Statut', 'Estimation', 'Actions'],
                    rowBuilder: (commande) => [
                        CommandeAdminManager.formatDate(commande.date_commande),
                        `<strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong>`,
                        commande.entrepot_nom,
                        `<strong>${CommandeAdminManager.formatPrice(commande.total)}</strong>`,
                        commande.date_livraison_prevue ? CommandeAdminManager.formatDate(commande.date_livraison_prevue) : '-',
                        CommandeAdminManager.getStatutBadge(commande.statut),
                        CommandeAdminManager.getEstimationHTML(commande),
                        `<button class="btn-action warning" onclick="CommandeAdminManager.marquerLivree(${commande.id})">Marquer livrée</button>
                         <button class="btn-action" onclick="CommandeAdminManager.voirDetails(${commande.id})">Détails</button>`
                    ]
                },
                toutes: {
                    headers: ['Date', 'Franchisé', 'Entrepôt', 'Total', 'Statut', 'Validé par', 'Distance', 'Actions'],
                    rowBuilder: (commande) => [
                        CommandeAdminManager.formatDate(commande.date_commande),
                        `<strong>${commande.franchise_nom} ${commande.franchise_prenom}</strong>`,
                        commande.entrepot_nom,
                        `<strong>${CommandeAdminManager.formatPrice(commande.total)}</strong>`,
                        CommandeAdminManager.getStatutBadge(commande.statut),
                        commande.admin_nom ? `${commande.admin_nom} ${commande.admin_prenom}` : '-',
                        commande.distance_km ? `📍 ${commande.distance_km}km` : '<small style="color: #999;">-</small>',
                        `<button class="btn-action" onclick="CommandeAdminManager.voirDetails(${commande.id})">Détails</button>`
                    ]
                }
            },
            statutLabels: {
                'en_attente': 'En attente',
                'validee': 'Validée', 
                'en_preparation': 'En préparation',
                'livree': 'Livrée',
                'annulee': 'Annulée'
            }
        },

        // ====== TABLEAUX ======
        async loadEnAttente() {
            await this.loadCommandes('en_attente');
        },
        async loadValidees() {
            await this.loadCommandes('validees');
        },
        async loadToutes() {
            await this.loadCommandes('toutes');
        },
        async loadCommandes(statut) {
            try {
                const url = statut === 'toutes'
                    ? this.config.endpoints.list
                    : `${this.config.endpoints.list}?statut=${statut}`;
                const commandes = await AdminCommon.utils.apiRequest(url);
                console.log('Chargement commandes:', url);
                console.log('Réponse API:', commandes);
                this.data[statut] = commandes;
                AdminCommon.utils.createTable({
                    containerId: `table-${statut}-container`,
                    headers: this.config.tables[statut].headers,
                    data: commandes,
                    rowBuilder: (commande) => {
                        const row = document.createElement('tr');
                        const cellsData = this.config.tables[statut].rowBuilder(commande);
                        const rowClass = cellsData[cellsData.length - 1];
                        const cells = cellsData.slice(0, -1);
                        if (rowClass) row.className = rowClass;
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucune commande trouvée'
                });
                if (statut === 'en_attente') this.updateBadges();
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des commandes', 'error');
            }
        },

        // ====== MODALS ======
        async voirDetails(commandeId) {
            try {
                const commande = await AdminCommon.utils.apiRequest(`${this.config.endpoints.details}?id=${commandeId}`);
                if (commande.error) {
                    AdminCommon.utils.showAlert('Erreur: ' + commande.error, 'error');
                    return;
                }
                const estimationHTML = commande.distance_km ? `
                    <div class="detail-section" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <h4 style="margin-top: 0;">Informations de livraison</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Distance:</strong> ${commande.distance_km} km</div>
                            <div><strong>Temps estimé:</strong> <span class="${commande.urgence_livraison === 'urgente' ? 'stock-warning' : 'stock-ok'}">${commande.temps_livraison_estime}</span></div>
                        </div>
                        ${commande.urgence_livraison === 'urgente' ? 
                            '<div style="background: #fff3e0; border: 1px solid #ff9800; border-radius: 4px; padding: 0.5rem; margin-top: 0.5rem;"><strong>⚠️ Livraison urgente recommandée</strong></div>' : 
                            ''
                        }
                    </div>
                ` : '';
                const content = `
                    <div class="detail-section" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <h4 style="margin-top: 0;">Informations générales</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0.5rem;">
                            <div><strong>Date de commande:</strong> ${this.formatDate(commande.date_commande)}</div>
                            <div><strong>Statut:</strong> ${this.getStatutBadge(commande.statut)}</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0.5rem;">
                            <div><strong>Franchisé:</strong> ${commande.franchise_nom} ${commande.franchise_prenom}</div>
                            <div><strong>Email:</strong> ${commande.franchise_email}</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Entrepôt:</strong> ${commande.entrepot_nom}</div>
                            <div><strong>Adresse:</strong> ${commande.entrepot_adresse}, ${commande.entrepot_ville}</div>
                        </div>
                        ${commande.date_livraison_prevue ? `<div style="margin-top: 0.5rem;"><strong>Date de livraison prévue:</strong> ${this.formatDate(commande.date_livraison_prevue)}</div>` : ''}
                        ${commande.admin_nom ? `<div><strong>Validé par:</strong> ${commande.admin_nom} ${commande.admin_prenom}</div>` : ''}
                    </div>
                    ${estimationHTML}
                    <div class="detail-section" style="background: #f8f9fa; padding: 1rem; border-radius: 6px;">
                        <h4 style="margin-top: 0;">Produits commandés</h4>
                        ${commande.details.map(detail => {
                            const stockSuffisant = detail.stock_disponible >= detail.quantite;
                            const stockClass = stockSuffisant ? 'stock-ok' : 'stock-warning';
                            const stockText = stockSuffisant ? 
                                `Stock: ${detail.stock_disponible} ${detail.stock_unite}` :
                                `! Stock insuffisant !: ${detail.stock_disponible}/${detail.quantite} ${detail.stock_unite}`;
                            return `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.5rem; background: white;">
                                    <div>
                                        <strong>${detail.produit_nom}</strong> (${detail.produit_type})<br>
                                        <small class="${stockClass}" style="font-weight: bold;">${stockText}</small>
                                    </div>
                                    <div style="text-align: right;">
                                        ${detail.quantite} × ${this.formatPrice(detail.prix_unitaire)}<br>
                                        <strong>${this.formatPrice(detail.prix_total)}</strong>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                        <div style="text-align: right; padding-top: 1rem; border-top: 2px solid #1976d2; margin-top: 1rem;">
                            <strong style="font-size: 1.2rem;">Total: ${this.formatPrice(commande.total)}</strong>
                        </div>
                    </div>
                    ${commande.commentaire_admin ? `
                        <div class="detail-section" style="background: #e3f2fd; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                            <h4 style="margin-top: 0; color: #1976d2;">Commentaires administrateur</h4>
                            <p style="margin: 0;">${commande.commentaire_admin}</p>
                        </div>
                    ` : ''}
                `;
                AdminCommon.utils.createModal({
                    title: `Détails de la commande #${commande.id}`,
                    content: content,
                    size: 'large'
                });
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des détails', 'error');
            }
        },

        validerCommande(commandeId) {
            AdminCommon.utils.createFormModal({
                title: `Valider la commande #${commandeId}`,
                fields: [
                    { 
                        name: 'date_livraison', 
                        label: 'Date de livraison prévue', 
                        type: 'date', 
                        required: true,
                        attributes: `min="${new Date().toISOString().split('T')[0]}"`,
                        help: 'Date à laquelle la commande sera livrée au franchisé'
                    },
                    { 
                        name: 'commentaire', 
                        label: 'Commentaire (optionnel)', 
                        type: 'textarea', 
                        attributes: 'rows="3" placeholder="Instructions particulières, notes..."'
                    },
                    { 
                        name: 'reserver_stocks', 
                        label: 'Réserver les stocks maintenant (recommandé)', 
                        type: 'checkbox',
                        defaultValue: true
                    }
                ],
                onSubmit: async (data, isEdit) => {
                    const success = await this.submitValidation(commandeId, 'valider', data);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await this.refreshData();
                    }
                }
            });
        },

        annulerCommande(commandeId) {
            AdminCommon.utils.createFormModal({
                title: `Annuler la commande #${commandeId}`,
                fields: [
                    { 
                        name: 'commentaire', 
                        label: 'Raison de l\'annulation', 
                        type: 'textarea', 
                        required: true,
                        attributes: 'rows="4" placeholder="Expliquez pourquoi cette commande est annulée..."'
                    }
                ],
                onSubmit: async (data, isEdit) => {
                    const success = await this.submitValidation(commandeId, 'annuler', data);
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await this.refreshData();
                    }
                }
            });
        },

        async marquerLivree(commandeId) {
            if (!confirm('Confirmer que cette commande a été livrée ? Les stocks seront automatiquement mis à jour.')) return;
            const success = await this.submitValidation(commandeId, 'livrer');
            if (success) await this.refreshData();
        },

        async submitValidation(commandeId, action, extraData = {}) {
            const formData = {
                commande_id: commandeId,
                action: action,
                ...extraData
            };
            try {
                const result = await AdminCommon.utils.apiRequest(this.config.endpoints.validate, {
                    method: 'POST',
                    data: formData,
                    showLoader: true
                });
                if (result.success) {
                    AdminCommon.utils.showAlert(result.message, 'success');
                    return true;
                } else {
                    AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                    return false;
                }
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur réseau lors de la validation', 'error');
                return false;
            }
        },

        // ====== BADGES & REFRESH ======
        async updateBadges() {
            try {
                const [attenteCommandes, valideesCommandes] = await Promise.all([
                    AdminCommon.utils.apiRequest(`${this.config.endpoints.list}?statut=en_attente`),
                    AdminCommon.utils.apiRequest(`${this.config.endpoints.list}?statut=validee`)
                ]);
                AdminCommon.utils.updateTabBadge('attente', attenteCommandes.length);
                AdminCommon.utils.updateTabBadge('validees', valideesCommandes.length);
            } catch (error) {
                console.error('Erreur lors de la mise à jour des badges:', error);
            }
        },

        async refreshData() {
            await Promise.all([
                this.loadEnAttente(),
                this.loadValidees(),
                this.updateBadges()
            ]);
        },

        startAutoRefresh() {
            setInterval(() => {
                if (document.getElementById('tab-en_attente').classList.contains('active')) {
                    this.loadEnAttente();
                    this.updateBadges();
                }
            }, 30000);
        },

        // ====== HELPERS ======
        calculateUrgence(commande) {
            const joursDiff = Math.floor((new Date() - new Date(commande.date_commande)) / (1000 * 60 * 60 * 24));
            const isUrgent = (joursDiff > 2 && commande.statut === 'en_attente') || commande.urgence_livraison === 'urgente';
            let display = `${joursDiff} jour(s)`;
            if (commande.distance_km) {
                const distanceInfo = `📍 ${commande.distance_km}km`;
                const tempsInfo = `⏱️ ${commande.temps_livraison_estime}`;
                if (commande.urgence_livraison === 'urgente') {
                    display = `🚨 ${joursDiff} jours<br><small style="color: #d32f2f;">${distanceInfo} - ${tempsInfo}</small>`;
                } else if (commande.urgence_livraison === 'attention') {
                    display = `⚠️ ${joursDiff} jours<br><small style="color: #f57c00;">${distanceInfo} - ${tempsInfo}</small>`;
                } else {
                    display = `${joursDiff} jour(s)<br><small style="color: #666;">${distanceInfo} - ${tempsInfo}</small>`;
                }
            } else if (isUrgent) {
                display = `🚨 ${joursDiff} jours<br><small style="color: #d32f2f;">Distance non calculable</small>`;
            }
            return { isUrgent, display };
        },
        getStatutBadge(statut) {
            const label = this.config.statutLabels[statut] || statut;
            return `<span class="statut-badge statut-${statut}">${label}</span>`;
        },
        getEstimationHTML(commande) {
            return commande.distance_km ? 
                `<small>📍 ${commande.distance_km}km<br>⏱️ ${commande.temps_livraison_estime}</small>` : 
                '<small style="color: #999;">Non calculable</small>';
        },
        formatDate(date) {
            return AdminCommon.utils.formatDate(date);
        },
        formatPrice(price) {
            return AdminCommon.utils.formatPrice(price);
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        CommandeAdminManager.loadEnAttente();
        CommandeAdminManager.updateBadges();
        CommandeAdminManager.startAutoRefresh();
    });
    </script>
</body>
</html>