<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement - Client</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <main class="main-content client">
            <h1>Paiement</h1>

            <!-- Total de la commande-->
            <div class="stats-grid" id="stats-client">
                <div class="stat-item info">
                    <div class="stat-number" id="total-commandes" style="color: #1976d2;"></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>

            <!--description de la commande-->
            <div class="section-card client">
                <h3>Récapitulatif de votre commande</h3>
                <p>Veuillez vérifier les détails de votre commande avant de procéder au paiement.</p>
                <table id="recap-ventes">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix Unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Formulaire de paiement -->
            <div class="section-card client">
                <h2>Informations de Paiement</h2>
                <p>Veuillez entrer vos informations de paiement pour finaliser votre commande.</p>
                <form id="paiement-form">
                    <input type="text" name="card_number" placeholder="Numéro de carte" required>
                    <input type="text" name="card_holder" placeholder="Nom du titulaire" required>
                    <input type="text" name="expiry_date" placeholder="Date d'expiration (MM/AA)" required>
                    <input type="text" name="cvv" placeholder="CVV" required>
                    <button type="submit" class="btn-nav">Payer</button>
                    <button type="button" class="btn-nav" style="background-color: #f44336;" id="btn-annuler">Annuler</button>
                </form>
                <div id="paiement-alert"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>
    <script>
        let commandeId = null;

        document.addEventListener('DOMContentLoaded', async function() {
            const urlParams = new URLSearchParams(window.location.search);
            commandeId = urlParams.get('commande_id');
            if (commandeId) await chargerCommande();

            document.getElementById('paiement-form').onsubmit = async function(e) {
                e.preventDefault();
                await processPaiement();
            };
            document.getElementById('btn-annuler').onclick = annulerCommande;
        });

        async function chargerCommande() {
            try {
                const response = await fetch(`../api/ventes/details.php?id=${commandeId}`);
                const result = await response.json();
                if (result.success && result.details) {
                    const tbody = document.querySelector('#recap-ventes tbody');
                    let total = 0;
                    tbody.innerHTML = '';
                    result.details.forEach(detail => {
                        const sousTotal = parseFloat(detail.prix_total || 0);
                        total += sousTotal;
                        tbody.innerHTML += `
                            <tr>
                                <td>${detail.nom}</td>
                                <td>${detail.quantite}</td>
                                <td>${parseFloat(detail.prix_unitaire).toFixed(2)}€</td>
                                <td>${sousTotal.toFixed(2)}€</td>
                            </tr>
                        `;
                    });
                    document.getElementById('total-commandes').textContent = AdminCommon.utils.formatPrice ? AdminCommon.utils.formatPrice(total) : total.toFixed(2) + '€';
                }
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur chargement commande', 'error');
            }
        }

        async function simulatePayment() {
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve({success: Math.random() > 0.1}); // 90% de chances de succès
                }, 1000);
            });
        }

        async function processPaiement() {
            try {
                const paiementResult = await simulatePayment();
                if (paiementResult.success) {
                    const res = await fetch('../api/ventes/uppdate.php', {
                        method: 'PUT',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            id: commandeId,
                            statut: 'valide'
                        })
                    });
                    const result = await res.json();
                    if (result.success) {
                        AdminCommon.utils.showAlert('Paiement réussi !', 'success');
                        setTimeout(() => {
                            window.location.href = 'mes_commandes.php?success=1';
                        }, 1200);
                    } else {
                        AdminCommon.utils.showAlert('Erreur lors de la validation de la commande', 'error');
                    }
                } else {
                    await fetch('../api/ventes/delete.php', {
                        method: 'DELETE',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: commandeId})
                    });
                    AdminCommon.utils.showAlert('Paiement échoué, commande annulée', 'error');
                    setTimeout(() => {
                        window.location.href = 'attente.html';
                    }, 1200);
                }
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur paiement', 'error');
            }
        }

        async function annulerCommande() {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                await fetch('../api/ventes/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: commandeId})
                });
                AdminCommon.utils.showAlert('Commande annulée', 'info');
                setTimeout(() => {
                    window.location.href = 'attente.html';
                }, 1200);
            }
        }
    </script>
</body>
</html>