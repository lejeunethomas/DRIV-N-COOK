<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Commandes - Client</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="commander.php">Commander</a>
            <a href="mes_commandes.php" class="active">Mes commandes</a>
            <a href="compte.php">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content client">
            <h1 class="client">Mes Commandes</h1>

            <div class="section-card client">
                <h2>Historique de mes commandes</h2>
                <p>Retrouvez ici toutes les commandes que vous avez passées auprès de nos food trucks.</p>
            </div>

            <div id="alert-container"></div>
            <div id="loading" style="text-align: center; padding: 2rem; display: none;">
                <p>Chargement de vos commandes...</p>
            </div>
            <div id="no-orders" style="text-align: center; padding: 2rem; color: #666; display: none;">
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="commander.php" class="btn-primary">Passer ma première commande</a>
            </div>
            <div id="commandes-table-container"></div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script>
    const CommandesClient = {
        config: {
            endpoints: {
                historique: '../api/ventes/list_by_user.php',
                details: '../api/ventes/details.php'
            },
            tableHeaders: ['Commande #', 'Food Truck', 'Plats commandés', 'Prix total', 'Date']
        },

        async init() {
            await this.loadHistorique();
        },

        async loadHistorique() {
            this.showLoading(true);
            this.showNoOrders(false);
            this.clearTable();

            try {
                const commandes = await AdminCommon.utils.apiRequest(this.config.endpoints.historique);
                this.showLoading(false);

                if (!Array.isArray(commandes) || commandes.length === 0) {
                    this.showNoOrders(true);
                    return;
                }

                this.displayCommandes(commandes);
            } catch (error) {
                this.showLoading(false);
                AdminCommon.utils.showAlert('Erreur lors du chargement de vos commandes', 'error');
                this.displayErrorTable();
            }
        },

        displayCommandes(commandes) {
            AdminCommon.utils.createTable({
                containerId: 'commandes-table-container',
                headers: this.config.tableHeaders,
                data: commandes,
                rowBuilder: (commande) => {
                    const commandeId = commande.id || 'N/A';
                    const foodTruck = commande.nom_camion || commande.camion_nom || 'Food truck non spécifié';
                    const produits = commande.produits_resume || 'Détails non disponibles';
                    const montant = AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(commande.montant) : parseFloat(commande.montant || 0).toFixed(2) + '€';
                    const dateCommande = commande.date_vente ?
                        AdminCommon.utils.formatDate(commande.date_vente, true) : 'Date inconnue';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>#${commandeId}</strong></td>
                        <td>${foodTruck}</td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                            title="${produits}">${produits}</td>
                        <td><strong>${montant}</strong></td>
                        <td>${dateCommande}</td>
                    `;
                    tr.style.cursor = 'pointer';
                    tr.title = 'Cliquez pour voir les détails';
                    tr.onclick = () => this.showDetails(commande.id);
                    return tr;
                },
                emptyMessage: 'Aucune commande trouvée'
            });
        },

        async showDetails(commandeId) {
            try {
                const result = await AdminCommon.utils.apiRequest(`${this.config.endpoints.details}?id=${commandeId}`);
                if (!result.success) {
                    AdminCommon.utils.showAlert('Impossible de charger les détails de cette commande', 'error');
                    return;
                }
                this.showDetailsModal(result, commandeId);
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des détails', 'error');
            }
        },

        showDetailsModal(commande, commandeId) {
            AdminCommon.utils.createModal({
                title: `🛒 Ma commande #${commandeId}`,
                content: `
                    <div class="detail-section">
                        <h4>Informations de ma commande</h4>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Date :</strong> ${AdminCommon.utils.formatDate(commande.date_vente, true)}</div>
                            <div><strong>Montant total :</strong> ${AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(commande.montant) : parseFloat(commande.montant).toFixed(2) + '€'}</div>
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Mode de paiement :</strong> ${commande.type_paiement || 'Non spécifié'}</div>
                            <div><strong>Food truck :</strong> ${commande.nom_camion || 'Non spécifié'}</div>
                        </div>
                    </div>
                    ${commande.details && commande.details.length > 0 ? `
                        <div class="detail-section">
                            <h4>Mes plats commandés</h4>
                            <table style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>Plat</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${commande.details.map(detail => `
                                        <tr>
                                            <td><strong>${detail.nom}</strong></td>
                                            <td>${detail.quantite}</td>
                                            <td>${AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(detail.prix_unitaire) : parseFloat(detail.prix_unitaire).toFixed(2) + '€'}</td>
                                            <td><strong>${AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(detail.prix_total) : parseFloat(detail.prix_total).toFixed(2) + '€'}</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : '<p style="text-align: center; color: #666;">Aucun détail disponible</p>'}
                `,
                actions: [
                    { text: 'Fermer', type: 'secondary', onclick: 'closeModal()' },
                    { text: 'Nouvelle commande', type: 'primary', onclick: "window.location.href='commander.php'" }
                ]
            });
        },

        showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        },

        showNoOrders(show) {
            document.getElementById('no-orders').style.display = show ? 'block' : 'none';
        },

        clearTable() {
            document.getElementById('commandes-table-container').innerHTML = '';
        },

        displayErrorTable() {
            document.getElementById('commandes-table-container').innerHTML = `
                <table>
                    <tbody>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #f44336; padding: 2rem;">
                                Erreur lors du chargement de vos commandes
                                <br><small>Veuillez réessayer plus tard</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            `;
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        CommandesClient.init();
    });
    </script>
</body>
</html>