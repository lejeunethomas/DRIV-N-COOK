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

            <div id="loading" style="text-align: center; padding: 2rem; display: none;">
                <p>Chargement de vos commandes...</p>
            </div>

            <div id="no-orders" style="text-align: center; padding: 2rem; color: #666; display: none;">
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="commander.php" class="btn-primary">Passer ma première commande</a>
            </div>

            <table id="commandes-table" style="display: none;">
                <thead>
                    <tr>
                        <th>Commande #</th>
                        <th>Food Truck</th>
                        <th>Plats commandés</th>
                        <th>Prix total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </main>
    </div>

    <script src="../js/vente.js"></script>

    <script>
        const CommandesClient = {
            async init() {
                await this.loadHistorique();
            },

            async loadHistorique() {
                const loading = document.getElementById('loading');
                const noOrders = document.getElementById('no-orders');
                const table = document.getElementById('commandes-table');
                
                try {
                    loading.style.display = 'block';
                    noOrders.style.display = 'none';
                    table.style.display = 'none';
                    
                    const response = await fetch('../api/ventes/list_by_user.php');
                    
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    
                    const commandes = await response.json();
                    
                    loading.style.display = 'none';
                    
                    if (!Array.isArray(commandes) || commandes.length === 0) {
                        noOrders.style.display = 'block';
                        return;
                    }
                    
                    table.style.display = 'table';
                    this.displayCommandes(commandes);
                    
                } catch (error) {
                    console.error('Erreur lors du chargement:', error);
                    loading.style.display = 'none';
                    
                    showAlert('Erreur lors du chargement de vos commandes', 'error');
                    
                    table.querySelector('tbody').innerHTML = `
                        <tr>
                            <td colspan="5" style="text-align: center; color: #f44336; padding: 2rem;">
                                Erreur lors du chargement de vos commandes
                                <br><small>Veuillez réessayer plus tard</small>
                            </td>
                        </tr>
                    `;
                    table.style.display = 'table';
                }
            },

            displayCommandes(commandes) {
                const tbody = document.querySelector('#commandes-table tbody');
                tbody.innerHTML = '';
                
                commandes.forEach(commande => {
                    const commandeId = commande.id || 'N/A';
                    const foodTruck = commande.nom_camion || commande.camion_nom || 'Food truck non spécifié';
                    const produits = commande.produits_resume || 'Détails non disponibles';
                    const montant = parseFloat(commande.montant || 0).toFixed(2);
                    const dateCommande = commande.date_vente ? 
                        new Date(commande.date_vente).toLocaleDateString('fr-FR', {
                            day: '2-digit',
                            month: '2-digit', 
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'Date inconnue';
                    
                    tbody.innerHTML += `
                        <tr onclick="CommandesClient.showDetails(${commande.id})" style="cursor: pointer;" title="Cliquez pour voir les détails">
                            <td><strong>#${commandeId}</strong></td>
                            <td>${foodTruck}</td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                title="${produits}">${produits}</td>
                            <td><strong>${montant}€</strong></td>
                            <td>${dateCommande}</td>
                        </tr>
                    `;
                });
            },

            async showDetails(commandeId) {
                try {
                    const response = await fetch(`../api/ventes/details.php?id=${commandeId}`);
                    
                    if (!response.ok) {
                        throw new Error('Erreur lors du chargement des détails');
                    }
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        showAlert('Impossible de charger les détails de cette commande', 'error');
                        return;
                    }
                    
                    this.showDetailsModal(result);
                    
                } catch (error) {
                    console.error('Erreur:', error);
                    showAlert('Erreur lors du chargement des détails', 'error');
                }
            },

            showDetailsModal(commande) {
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>🛒 Ma commande #${commande.id}</h3>
                        
                        <div class="detail-section">
                            <h4>Informations de ma commande</h4>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div><strong>Date :</strong> ${new Date(commande.date_vente).toLocaleDateString('fr-FR', {
                                    weekday: 'long',
                                    day: 'numeric',
                                    month: 'long',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</div>
                                <div><strong>Montant total :</strong> ${parseFloat(commande.montant).toFixed(2)}€</div>
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
                                                <td>${parseFloat(detail.prix_unitaire).toFixed(2)}€</td>
                                                <td><strong>${parseFloat(detail.prix_total).toFixed(2)}€</strong></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : '<p style="text-align: center; color: #666;">Aucun détail disponible</p>'}
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                            <button type="button" class="btn-primary" onclick="window.location.href='commander.php'">
                                Nouvelle commande
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            CommandesClient.init();
        });
    </script>
</body>
</html>