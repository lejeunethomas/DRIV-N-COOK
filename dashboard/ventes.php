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
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('ventes', loadVentesData)">
                    Toutes les ventes
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('produits', loadProduitsVendus)">
                    Produits vendus
                </button>
            </div>

            <div id="tab-ventes" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Liste des ventes</h3>
                    <button class="add-btn" onclick="showAddVenteModal()">+ Enregistrer une vente</button>
                </div>
                
                <div class="filters" style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                    <label for="filter-camion">Camion :</label>
                    <select id="filter-camion" onchange="loadVentesData()">
                        <option value="">Tous les camions</option>
                    </select>
                    
                    <label for="filter-periode">Période :</label>
                    <select id="filter-periode" onchange="loadVentesData()">
                        <option value="">Toutes les périodes</option>
                        <option value="aujourdhui">Aujourd'hui</option>
                        <option value="semaine">Ce mois</option>
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
    <script src="../js/vente.js"></script>

    <script>
        const ventesAdminManager = {
            config: {
                endpoints: {
                    ventes: '../api/ventes/list.php',              
                    camions: '../api/camions/list.php',            
                    stats: '../api/ventes/stats.php?global=1',
                    produits: '../api/ventes/produits_vendus.php', 
                    deleteVente: '../api/ventes/delete_admin.php'  
                },
                
                tables: {
                    ventes: {
                        headers: ['ID', 'Camion', 'Franchisé', 'Produits', 'Montant', 'Date', 'Actions'],
                        rowBuilder: (vente) => [
                            `#${vente.id}`,
                            vente.nom_camion || 'N/A',                    
                            vente.franchise_nom ? `${vente.franchise_nom} ${vente.franchise_prenom || ''}` : 'N/A',
                            `${vente.nb_produits || 0} produit(s)`,       
                            `<strong>${AdminCommon.utils.formatPrice(vente.montant)}</strong>`,
                            AdminCommon.utils.formatDate(vente.date_vente, true),
                            `<button class="btn-action" onclick="viewVenteDetails(${vente.id})">Détails</button>
                             <button class="btn-action danger" onclick="deleteVenteAdmin(${vente.id})">Supprimer</button>`
                        ]
                    },
                    produits: {
                        headers: ['Produit', 'Unité', 'Quantité vendue', 'CA généré', 'Nb ventes'],
                        rowBuilder: (produit) => [
                            `<strong>${produit.nom}</strong>`,
                            produit.unite || 'N/A',
                            `${produit.quantite_totale || 0}`,
                            `<strong>${AdminCommon.utils.formatPrice(produit.ca_total || 0)}</strong>`,
                            produit.nb_ventes || 0
                        ]
                    }
                }
            },
            data: {
                ventes: []
            }
        };

        document.addEventListener('DOMContentLoaded', async function() {
            await Promise.all([
                loadCamionsFilter(),
                loadVentesData(),
                loadStats()
            ]);
        });

        async function loadVentesData() {
            const camion = document.getElementById('filter-camion')?.value || '';
            const periode = document.getElementById('filter-periode')?.value || '';
            
            try {
                const params = new URLSearchParams();
                if (camion) params.append('camion', camion);
                if (periode) params.append('periode', periode);
                
                const url = ventesAdminManager.config.endpoints.ventes + 
                           (params.toString() ? '?' + params.toString() : '');
                
                const response = await fetch(url);
                ventesAdminManager.data.ventes = await response.json();
                
                AdminCommon.utils.createTable({
                    containerId: 'ventes-table-container',
                    headers: ventesAdminManager.config.tables.ventes.headers,
                    data: ventesAdminManager.data.ventes,
                    rowBuilder: (vente) => {
                        const row = document.createElement('tr');
                        const cells = ventesAdminManager.config.tables.ventes.rowBuilder(vente);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucune vente trouvée'
                });
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des ventes', 'error');
                console.error('Erreur:', error);
            }
        }

        async function loadProduitsVendus() {
            try {
                const response = await fetch(ventesAdminManager.config.endpoints.produits);
                const produits = await response.json();
                
                AdminCommon.utils.createTable({
                    containerId: 'produits-vendus-table-container',
                    headers: ventesAdminManager.config.tables.produits.headers,
                    data: produits,
                    rowBuilder: (produit) => {
                        const row = document.createElement('tr');
                        const cells = ventesAdminManager.config.tables.produits.rowBuilder(produit);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucun produit vendu'
                });
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des produits', 'error');
            }
        }

        async function loadStats() {
            try {
                const response = await fetch(ventesAdminManager.config.endpoints.stats);
                const stats = await response.json();
                
                if (stats.error) {
                    console.error('Erreur stats:', stats.error);
                    return;
                }
                
                updateStatElement('total-ventes', AdminCommon.utils.formatPrice(stats.total || 0));
                updateStatElement('ventes-jour', AdminCommon.utils.formatPrice(stats.aujourdhui || 0));
                updateStatElement('ventes-semaine', AdminCommon.utils.formatPrice(stats.semaine || 0));
                updateStatElement('ventes-mois', AdminCommon.utils.formatPrice(stats.mois || 0));
                
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
            }
        }

        async function loadCamionsFilter() {
            try {
                const response = await fetch(ventesAdminManager.config.endpoints.camions);
                const camions = await response.json();
                
                const select = document.getElementById('filter-camion');
                camions.forEach(camion => {
                    select.innerHTML += `<option value="${camion.id}">${camion.nom} - ${camion.localisation || 'Position inconnue'}</option>`;
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des camions:', error);
            }
        }

        function showAddVenteModal() {
            if (typeof window.showAddVenteModal === 'function') {
                window.showAddVenteModal();
            } else {
                AdminCommon.utils.showAlert('Fonctionnalité d\'ajout en cours de développement', 'info');
            }
        }

        // FONCTION GÉNÉRIQUE POUR VOIR LES DÉTAILS
        async function viewVenteDetails(venteId) {
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
                console.error('Erreur:', error);
            }
        }
        async function deleteVenteAdmin(venteId) {
            const success = await AdminCommon.utils.deleteData(
                ventesAdminManager.config.endpoints.deleteVente,
                { id: venteId },
                'Êtes-vous sûr de vouloir supprimer cette vente ? Cette action est irréversible.'
            );
            
            if (success) {
                await loadVentesData();
                await loadStats();
            }
        }

        function updateStatElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
    </script>
</body>
</html>