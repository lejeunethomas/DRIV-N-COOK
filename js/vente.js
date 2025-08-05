/**
 * DRIV'N'COOK - Gestion des ventes franchisé
 * Script pour l'interface de saisie des ventes
 */

// Variables globales
let produits = [];
let ventesAujourdhui = [];
let totalJournalier = 0;
let venteEnCours = [];

// CHARGEMENT INITIAL

document.addEventListener('DOMContentLoaded', function() {
    loadProduits();
    loadVentesStats();
    loadVentesAujourdhui();
    
    // Initialiser le bouton d'ajout de vente
    const addBtn = document.getElementById('add-ventes-btn');
    if (addBtn) {
        addBtn.onclick = showAddVenteModal;
    }
});

// CHARGEMENT DES DONNÉES

/**
 * Charger les produits disponibles
 */
async function loadProduits() {
    try {
        const response = await fetch('../api/produits/list.php');
        produits = await response.json();
    } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
        showAlert('Erreur lors du chargement des produits', 'error');
    }
}

/**
 * Charger les statistiques de ventes
 */
async function loadVentesStats() {
    try {
        const response = await fetch('../api/ventes/stats_by_user.php');
        const stats = await response.json();
        
        // Mettre à jour les éléments statistiques s'ils existent
        updateStatElement('total-ventes', (stats.total_general || 0).toFixed(2) + '€');
        updateStatElement('Ventes-jour', (stats.ventes_jour || 0).toFixed(2) + '€');
        updateStatElement('Ventes-semaine', (stats.ventes_semaine || 0).toFixed(2) + '€');
        updateStatElement('Ventes-mois', (stats.total_mois || 0).toFixed(2) + '€');
        
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        // Afficher des valeurs par défaut
        updateStatElement('total-ventes', 'N/A');
        updateStatElement('Ventes-jour', 'N/A');
        updateStatElement('Ventes-semaine', 'N/A');
        updateStatElement('Ventes-mois', 'N/A');
    }
}

/**
 * Charger les ventes d'aujourd'hui
 */
async function loadVentesAujourdhui() {
    try {
        const response = await fetch('../api/ventes/list_by_user.php?periode=aujourdhui');
        const ventes = await response.json();
        
        displayVentesTable(ventes);
        
    } catch (error) {
        console.error('Erreur lors du chargement des ventes:', error);
    }
}

/**
 * Afficher les ventes dans le tableau
 */
function displayVentesTable(ventes) {
    const tbody = document.querySelector('#ventes-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!Array.isArray(ventes) || ventes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: #666; padding: 2rem;">
                    Aucune vente enregistrée aujourd'hui
                </td>
            </tr>
        `;
        return;
    }
    
    ventes.forEach(vente => {
        const details = Array.isArray(vente.details) ? vente.details : [];
        const produitsResume = details.map(d => `${d.nom} (x${d.quantite})`).join(', ');
        
        tbody.innerHTML += `
            <tr>
                <td>${produitsResume || 'N/A'}</td>
                <td>${details.reduce((sum, d) => sum + parseFloat(d.quantite || 0), 0)}</td>
                <td>${parseFloat(vente.montant || 0).toFixed(2)}€</td>
                <td>${new Date(vente.date_vente).toLocaleString('fr-FR')}</td>
                <td class="actions">
                    <button class="btn-action" onclick="viewVenteDetails(${vente.id})">Détails</button>
                    <button class="btn-action danger" onclick="deleteVente(${vente.id})">Supprimer</button>
                </td>
            </tr>
        `;
    });
}

// MODAL D'AJOUT DE VENTE

/**
 * Afficher le modal d'ajout de vente
 */
function showAddVenteModal() {
    venteEnCours = [];
    
    const modal = document.createElement('div');
    modal.className = 'modal modal-large';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>Nouvelle vente</h3>
            
            <!-- Sélection des produits -->
            <div class="section-produits">
                <h4>Sélection des produits</h4>
                <div class="form-group">
                    <label for="produit-select">Ajouter un produit *</label>
                    <select id="produit-select">
                        <option value="">Sélectionner un produit</option>
                        ${produits.map(p => `
                            <option value="${p.id}" data-prix="${p.prix_unitaire}" data-nom="${p.nom}">
                                ${p.nom} - ${parseFloat(p.prix_unitaire).toFixed(2)}€
                            </option>
                        `).join('')}
                    </select>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                    <div class="form-group">
                        <label for="quantite-produit">Quantité</label>
                        <input type="number" id="quantite-produit" min="1" step="1" value="1">
                    </div>
                    <div class="form-group">
                        <label for="prix-unitaire">Prix unitaire (€)</label>
                        <input type="number" id="prix-unitaire" step="0.01" min="0" readonly>
                    </div>
                    <button type="button" class="btn-primary" onclick="ajouterProduitVente()">
                    + Ajouter
                    </button>
                </div>
            </div>
            
            <!-- Liste des produits ajoutés -->
            <div id="produits-vente" class="section-resume" style="display: none;">
                <h4>Produits de la vente</h4>
                <div id="liste-produits-vente"></div>
                <div class="total-vente">
                    <strong>Total : <span id="total-vente">0.00€</span></strong>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="button" class="btn-primary" id="finaliser-vente" onclick="finaliserVente()" disabled>
                    Finaliser la vente
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Gérer le changement de produit sélectionné
    document.getElementById('produit-select').onchange = function() {
        const option = this.selectedOptions[0];
        const prix = option.dataset.prix || 0;
        document.getElementById('prix-unitaire').value = parseFloat(prix).toFixed(2);
    };
}

/**
 * Ajouter un produit à la vente en cours
 */
function ajouterProduitVente() {
    const produitSelect = document.getElementById('produit-select');
    const quantite = parseInt(document.getElementById('quantite-produit').value) || 1;
    const prixUnitaire = parseFloat(document.getElementById('prix-unitaire').value) || 0;
    
    if (!produitSelect.value) {
        showAlert('Veuillez sélectionner un produit', 'error');
        return;
    }
    
    if (quantite <= 0) {
        showAlert('La quantité doit être supérieure à 0', 'error');
        return;
    }
    
    const produitId = parseInt(produitSelect.value);
    const produitNom = produitSelect.selectedOptions[0].dataset.nom;
    
    // Vérifier si le produit est déjà dans la vente
    const existingIndex = venteEnCours.findIndex(item => item.produit_id === produitId);
    
    if (existingIndex >= 0) {
        // Mettre à jour la quantité
        venteEnCours[existingIndex].quantite += quantite;
        venteEnCours[existingIndex].prix_total = venteEnCours[existingIndex].quantite * prixUnitaire;
    } else {
        // Ajouter nouveau produit
        venteEnCours.push({
            produit_id: produitId,
            nom: produitNom,
            quantite: quantite,
            prix_unitaire: prixUnitaire,
            prix_total: quantite * prixUnitaire
        });
    }
    
    // Réinitialiser les champs
    produitSelect.value = '';
    document.getElementById('quantite-produit').value = 1;
    document.getElementById('prix-unitaire').value = '';
    
    // Mettre à jour l'affichage
    updateVenteResume();
}

/**
 * Mettre à jour l'affichage du résumé de vente
 */
function updateVenteResume() {
    const container = document.getElementById('produits-vente');
    const liste = document.getElementById('liste-produits-vente');
    const totalElement = document.getElementById('total-vente');
    const finaliserBtn = document.getElementById('finaliser-vente');
    
    if (venteEnCours.length === 0) {
        container.style.display = 'none';
        finaliserBtn.disabled = true;
        return;
    }
    
    container.style.display = 'block';
    finaliserBtn.disabled = false;
    
    let total = 0;
    liste.innerHTML = '';
    
    venteEnCours.forEach((item, index) => {
        total += item.prix_total;
        
        liste.innerHTML += `
            <div class="produit-vente-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 0.5rem;">
                <div>
                    <strong>${item.nom}</strong><br>
                    <small>${item.quantite} × ${item.prix_unitaire.toFixed(2)}€</small>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <strong>${item.prix_total.toFixed(2)}€</strong>
                    <button type="button" class="btn-action danger" onclick="retirerProduitVente(${index})" title="Retirer">
                        🗑️
                    </button>
                </div>
            </div>
        `;
    });
    
    totalElement.textContent = total.toFixed(2) + '€';
}

/**
 * Retirer un produit de la vente en cours
 */
function retirerProduitVente(index) {
    if (index >= 0 && index < venteEnCours.length) {
        venteEnCours.splice(index, 1);
        updateVenteResume();
    }
}

/**
 * Finaliser la vente
 */
async function finaliserVente() {
    if (venteEnCours.length === 0) {
        showAlert('Aucun produit dans la vente', 'error');
        return;
    }
    
    const total = venteEnCours.reduce((sum, item) => sum + item.prix_total, 0);
    
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'modal';
    confirmationModal.innerHTML = `
        <div class="modal-content">
            <h3>Confirmer la vente</h3>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                <h4>Récapitulatif :</h4>
                ${venteEnCours.map(item => `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>${item.nom} × ${item.quantite}</span>
                        <span>${item.prix_total.toFixed(2)}€</span>
                    </div>
                `).join('')}
                <hr>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                    <span>Total :</span>
                    <span>${total.toFixed(2)}€</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeConfirmationModal()">Retour</button>
                <button type="button" class="btn-primary" onclick="enregistrerVente()">
                    Enregistrer la vente
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmationModal);
    
    // Fonctions pour la modal de confirmation
    window.closeConfirmationModal = function() {
        confirmationModal.remove();
    };
    
    window.enregistrerVente = async function() {
        try {
            const venteData = {
                montant: total,
                produits: venteEnCours
            };
            
            const response = await fetch('../api/ventes/add.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(venteData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('Vente enregistrée avec succès !', 'success');
                confirmationModal.remove();
                closeModal();
                
                loadVentesStats();
                loadVentesAujourdhui();
            } else {
                showAlert('Erreur lors de l\'enregistrement : ' + result.message, 'error');
            }
            
        } catch (error) {
            console.error('Erreur lors de l\'enregistrement de la vente:', error);
            showAlert('Erreur réseau lors de l\'enregistrement', 'error');
        }
    };
}

// GESTION DES VENTES EXISTANTES

/**
 * Voir les détails d'une vente
 */
async function viewVenteDetails(venteId) {
    try {
        const response = await fetch(`../api/ventes/details.php?id=${venteId}`);
        const vente = await response.json();
        
        if (!vente.success) {
            showAlert('Erreur lors du chargement des détails', 'error');
            return;
        }
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>📋 Détails de la vente #${venteId}</h3>
                
                <div class="vente-info">
                    <div class="form-row">
                        <div><strong>Date :</strong> ${new Date(vente.date_vente).toLocaleString('fr-FR')}</div>
                        <div><strong>Montant total :</strong> ${parseFloat(vente.montant).toFixed(2)}€</div>
                    </div>
                </div>
                
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
                        ${(vente.details || []).map(detail => `
                            <tr>
                                <td>${detail.nom}</td>
                                <td>${detail.quantite}</td>
                                <td>${parseFloat(detail.prix_unitaire).toFixed(2)}€</td>
                                <td>${parseFloat(detail.prix_total).toFixed(2)}€</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
    } catch (error) {
        console.error('Erreur lors du chargement des détails:', error);
        showAlert('Erreur lors du chargement des détails', 'error');
    }
}

/**
 * Supprimer une vente
 */
async function deleteVente(venteId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette vente ?\n\nCette action est irréversible.')) {
        return;
    }
    
    try {
        const response = await fetch('../api/ventes/delete.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: venteId})
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Vente supprimée avec succès', 'success');
            loadVentesStats();
            loadVentesAujourdhui();
        } else {
            showAlert('Erreur lors de la suppression : ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showAlert('Erreur réseau lors de la suppression', 'error');
    }
}

// FONCTIONS UTILITAIRES

/**
 * Mettre à jour un élément statistique
 */
function updateStatElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

/**
 * Fermer la modal active
 */
function closeModal() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => modal.remove());
}

/**
 * Afficher une alerte
 */
function showAlert(message, type = 'info') {
    // Créer l'alerte
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        max-width: 400px;
    `;
    
    // Styles selon le type
    const styles = {
        'success': 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
        'error': 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
        'warning': 'background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;',
        'info': 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'
    };
    
    alert.style.cssText += styles[type] || styles.info;
    alert.textContent = message;
    
    document.body.appendChild(alert);
    
    // Supprimer après 5 secondes
    setTimeout(() => {
        if (alert.parentNode){
            alert.remove();
        }
    }, 5000);
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});