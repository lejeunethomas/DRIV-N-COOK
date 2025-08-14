<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des ventes - Admin</title>
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
            <a href="ventes.php" class="active">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content admin">
            <h1 class="admin">Gestion des ventes</h1>

            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-item success">
                    <div class="stat-number" id="total-ventes" style="color: #4caf50;">0€</div>
                    <div class="stat-label">Ventes totales</div>
                </div>
                <div class="stat-item info">
                    <div class="stat-number" id="ventes-jour" style="color: #2196f3;">0€</div>
                    <div class="stat-label">Aujourd'hui</div>
                </div>
                <div class="stat-item warning">
                    <div class="stat-number" id="ventes-semaine" style="color: #ff9800;">0€</div>
                    <div class="stat-label">Cette semaine</div>
                </div>
                <div class="stat-item primary">
                    <div class="stat-number" id="ventes-mois" style="color: #1976d2;">0€</div>
                    <div class="stat-label">Ce mois</div>
                </div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('ventes', VentesAdminManager.loadVentes.bind(VentesAdminManager))">
                    Toutes les ventes
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('produits', VentesAdminManager.loadProduitsVendus.bind(VentesAdminManager))">
                    Produits vendus
                </button>
            </div>

            <div id="tab-ventes" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Liste des ventes</h3>
                    <button class="add-btn" onclick="VentesAdminManager.showAddModal()">+ Enregistrer une vente</button>
                </div>
                <div class="filters" style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                    <label for="filter-camion">Camion :</label>
                    <select id="filter-camion" onchange="VentesAdminManager.loadVentes()">
                        <option value="">Tous les camions</option>
                    </select>
                    <label for="filter-periode">Période :</label>
                    <select id="filter-periode" onchange="VentesAdminManager.loadVentes()">
                        <option value="">Toutes les périodes</option>
                        <option value="aujourdhui">Aujourd'hui</option>
                        <option value="semaine">Cette semaine</option>
                        <option value="mois">Ce mois</option>
                    </select>
                </div>
                <div id="ventes-table-container"></div>
            </div>

            <div id="tab-produits" class="tab-content">
                <h3>Produits les plus vendus</h3>
                <div id="produits-vendus-table-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script>
    const VentesAdminManager = {
        config: {
            endpoints: {
                ventes: '../api/ventes/list.php',
                camions: '../api/camions/list.php',
                stats: '../api/ventes/stats.php?global=1',
                produits: '../api/ventes/produits_vendus.php',
                deleteVente: '../api/ventes/delete_admin.php'
            },
            headers: {
                ventes: ['ID', 'Camion', 'Franchisé', 'Produits', 'Montant', 'Date', 'Actions'],
                produits: ['Produit', 'Unité', 'Quantité vendue', 'CA généré', 'Nb ventes']
            }
        },
        data: {
            ventes: [],
            produits: [],
            camions: []
        },

        async init() {
            await Promise.all([
                this.loadCamionsFilter(),
                this.loadVentes(),
                this.loadStats()
            ]);
        },

        async loadVentes() {
            const camion = document.getElementById('filter-camion')?.value || '';
            const periode = document.getElementById('filter-periode')?.value || '';
            try {
                const params = new URLSearchParams();
                if (camion) params.append('camion', camion);
                if (periode) params.append('periode', periode);
                const url = this.config.endpoints.ventes + (params.toString() ? '?' + params.toString() : '');
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
                this.data.ventes = await response.json();
                this.displayVentes();
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des ventes: ' + error.message, 'error');
                this.data.ventes = [];
                this.displayVentes();
            }
        },

        displayVentes() {
            const container = document.getElementById('ventes-table-container');
            if (!this.data.ventes || this.data.ventes.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Aucune vente trouvée</p>';
                return;
            }
            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>${this.config.headers.ventes.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${this.data.ventes.map(vente => {
                        const cells = this.buildVenteRow(vente);
                        return `<tr>${cells.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
                    }).join('')}
                </tbody>
            `;
            container.innerHTML = '';
            container.appendChild(table);
        },

        buildVenteRow(vente) {
            return [
                `#${vente.id}`,
                vente.nom_camion || 'N/A',
                vente.franchise_nom ? `${vente.franchise_nom} ${vente.franchise_prenom || ''}` : 'N/A',
                `${vente.nb_produits || 0} produit(s)`,
                `<strong>${AdminCommon.utils.formatPrice(vente.montant)}</strong>`,
                AdminCommon.utils.formatDate(vente.date_vente, true),
                `<button class="btn-action" onclick="VentesAdminManager.viewDetails(${vente.id})">Détails</button>
                 <button class="btn-action danger" onclick="VentesAdminManager.deleteVente(${vente.id})">Supprimer</button>`
            ];
        },

        async loadProduitsVendus() {
            try {
                const response = await fetch(this.config.endpoints.produits);
                if (!response.ok) throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
                const produits = await response.json();
                if (!Array.isArray(produits)) {
                    throw new Error(produits && produits.error ? produits.error : "Format de données inattendu");
                }
                this.data.produits = produits;
                this.displayProduitsVendus();
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des produits: ' + error.message, 'error');
                this.data.produits = [];
                this.displayProduitsVendus();
            }
        },

        displayProduitsVendus() {
            const container = document.getElementById('produits-vendus-table-container');
            if (!this.data.produits || this.data.produits.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">Aucun produit vendu</p>';
                return;
            }
            const table = document.createElement('table');
            table.innerHTML = `
                <thead>
                    <tr>${this.config.headers.produits.map(h => `<th>${h}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${this.data.produits.map(produit => {
                        const cells = this.buildProduitRow(produit);
                        return `<tr>${cells.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
                    }).join('')}
                </tbody>
            `;
            container.innerHTML = '';
            container.appendChild(table);
        },

        buildProduitRow(produit) {
            return [
                `<strong>${produit.nom}</strong>`,
                produit.unite || 'N/A',
                `${produit.quantite_totale || 0}`,
                `<strong>${AdminCommon.utils.formatPrice(produit.ca_total || 0)}</strong>`,
                produit.nb_ventes || 0
            ];
        },

        async loadStats() {
            try {
                const response = await fetch(this.config.endpoints.stats);
                const stats = await response.json();
                if (stats.error) return;
                this.updateStatElement('total-ventes', AdminCommon.utils.formatPrice(stats.total || 0));
                this.updateStatElement('ventes-jour', AdminCommon.utils.formatPrice(stats.aujourdhui || 0));
                this.updateStatElement('ventes-semaine', AdminCommon.utils.formatPrice(stats.semaine || 0));
                this.updateStatElement('ventes-mois', AdminCommon.utils.formatPrice(stats.mois || 0));
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
            }
        },

        async loadCamionsFilter() {
            try {
                const response = await fetch(this.config.endpoints.camions);
                if (!response.ok) throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
                this.data.camions = await response.json();
                const select = document.getElementById('filter-camion');
                this.data.camions.forEach(camion => {
                    select.innerHTML += `<option value="${camion.id}">${camion.nom} - ${camion.localisation || 'Position inconnue'}</option>`;
                });
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des camions: ' + error.message, 'error');
                this.data.camions = [];
            }
        },

        showAddModal() {
            // Modal d'ajout manuel
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter une vente manuellement</h3>
                    <form id="add-vente-form">
                        <div class="form-group">
                            <label for="add-camion">Camion *</label>
                            <select id="add-camion" name="camion_id" required>
                                <option value="">Sélectionner un camion</option>
                                ${this.data.camions.map(c => `<option value="${c.id}">${c.nom}</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-montant">Montant total (€) *</label>
                            <input type="number" id="add-montant" name="montant" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="add-date">Date de vente *</label>
                            <input type="datetime-local" id="add-date" name="date_vente" required>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="AdminCommon.utils.closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
            document.getElementById('add-vente-form').addEventListener('submit', this.handleAddVente.bind(this));
        },

        async handleAddVente(e) {
            e.preventDefault();
            const form = e.target;
            const data = {
                camion_id: form.camion_id.value,
                montant: form.montant.value,
                date_vente: form.date_vente.value
            };
            if (!data.camion_id || !data.montant || !data.date_vente) {
                AdminCommon.utils.showAlert('Veuillez remplir tous les champs obligatoires', 'error');
                return;
            }
            try {
                const response = await fetch('../api/ventes/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    AdminCommon.utils.closeModal();
                    AdminCommon.utils.showAlert('Vente ajoutée avec succès', 'success');
                    await this.loadVentes();
                    await this.loadStats();
                } else {
                    AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur réseau lors de l\'ajout', 'error');
            }
        },

        async viewDetails(venteId) {
            try {
                const response = await fetch(`../api/ventes/details_admin.php?id=${venteId}`);
                const vente = await response.json();
                if (vente.error) {
                    AdminCommon.utils.showAlert('Erreur: ' + vente.error, 'error');
                    return;
                }
                const content = `
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Vente #:</strong> ${vente.id}</div>
                            <div><strong>Date:</strong> ${AdminCommon.utils.formatDate(vente.date_vente, true)}</div>
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Camion:</strong> ${vente.nom_camion || 'N/A'}</div>
                            <div><strong>Franchisé:</strong> ${vente.franchisé_nom ? vente.franchisé_nom + ' ' + (vente.franchisé_prenom || '') : 'N/A'}</div>
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Statut:</strong> <span class="badge badge-${vente.statut || 'info'}">${vente.statut || 'N/A'}</span></div>
                            <div><strong>Type paiement:</strong> ${vente.type_paiement || 'N/A'}</div>
                        </div>
                    </div>
                    ${vente.details && vente.details.length > 0 ? `
                        <h4>Produits vendus :</h4>
                        <table style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${vente.details.map(detail => `
                                    <tr>
                                        <td><strong>${detail.produit_nom}</strong></td>
                                        <td>${detail.quantite}</td>
                                        <td>${AdminCommon.utils.formatPrice(detail.prix_unitaire)}</td>
                                        <td><strong>${AdminCommon.utils.formatPrice(detail.prix_total)}</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div style="text-align: right; padding-top: 1rem; border-top: 2px solid #1976d2; margin-top: 1rem;">
                            <strong style="font-size: 1.2rem;">Total: ${AdminCommon.utils.formatPrice(vente.montant)}</strong>
                        </div>
                    ` : '<p style="text-align: center; color: #666;">Aucun détail disponible</p>'}
                `;
                AdminCommon.utils.createModal({
                    title: `Détails de la vente #${vente.id}`,
                    content: content,
                    size: 'large'
                });
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des détails', 'error');
            }
        },

        async deleteVente(venteId) {
            const success = await AdminCommon.utils.deleteData(
                this.config.endpoints.deleteVente,
                { id: venteId },
                'Êtes-vous sûr de vouloir supprimer cette vente ? Cette action est irréversible.'
            );
            if (success) {
                await this.loadVentes();
                await this.loadStats();
            }
        },

        updateStatElement(id, value) {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        VentesAdminManager.init();
    });
    </script>
</body>
</html>