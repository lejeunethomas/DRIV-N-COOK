/**
 * DRIV'N'COOK - Gestion des menus franchisé
 * Script pour l'interface de gestion des plats
 */

// Variables globales
let menus = [];

// CHARGEMENT INITIAL
document.addEventListener('DOMContentLoaded', function() {
    loadMenus();
    
    // Initialiser le bouton d'ajout
    const addBtn = document.getElementById('add-plat-btn');
    if (addBtn) {
        addBtn.onclick = showAddPlatModal;
    }
});

// CHARGEMENT DES DONNÉES
/**
 * Charger tous les plats du menu
 */
async function loadMenus() {
    try {
        const response = await fetch('../api/menu/list.php');
        menus = await response.json();
        displayMenus();
    } catch (error) {
        console.error('Erreur lors du chargement des menus:', error);
        showAlert('Erreur lors du chargement des menus', 'error');
    }
}

/**
 * Afficher les plats dans le tableau
 */
function displayMenus() {
    const tbody = document.getElementById('menu-list');
    tbody.innerHTML = '';
    
    if (!Array.isArray(menus) || menus.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; color: #666; padding: 2rem;">
                    Aucun plat dans votre menu. Commencez par ajouter des plats !
                </td>
            </tr>
        `;
        return;
    }
    
    // Regrouper par catégorie
    const categories = {
        'plat': 'Plats principaux',
        'boisson': 'Boissons',
        'dessert': 'Desserts',
        'accompagnement': 'Accompagnements'
    };
    
    let currentCategorie = '';
    
    menus.forEach(plat => {
        // Ajouter séparateur de catégorie
        if (currentCategorie !== plat.categorie) {
            currentCategorie = plat.categorie;
            tbody.innerHTML += `
                <tr class="category-separator">
                    <td colspan="4" style="background: #f0f0f0; font-weight: bold; color: #1976d2; padding: 0.8rem;">
                        📋 ${categories[plat.categorie] || plat.categorie}
                    </td>
                </tr>
            `;
        }
            
        const allergenesBadge = plat.allergenes ? 
            `<span class="badge badge-warning" title="Allergènes: ${plat.allergenes}">⚠️ Allergènes</span>` : '';
            
        tbody.innerHTML += `
            <tr>
                <td>
                    <strong>${plat.nom}</strong>
                </td>
                <td>
                    ${plat.description || '<em>Aucune description</em>'}
                    ${plat.ingredients ? `<br><small style="color: #666;">Ingrédients: ${plat.ingredients}</small>` : ''}
                </td>
                <td>
                    <strong>${parseFloat(plat.prix).toFixed(2)}€</strong><br>
                    ${allergenesBadge}
                </td>
                <td class="actions">
                    <button class="btn-action" onclick="editPlat(${plat.id})">Modifier</button>
                    <button class="btn-action danger" onclick="deletePlat(${plat.id})">Supprimer</button>
                </td>
            </tr>
        `;
    });
}

// MODAL D'AJOUT/MODIFICATION DE PLAT
/**
 * Afficher le modal d'ajout de plat
 */
function showAddPlatModal() {
    showPlatModal();
}

/**
 * Afficher le modal de plat (ajout ou modification)
 */
function showPlatModal(plat = null) {
    const isEdit = plat !== null;
    const title = isEdit ? 'Modifier le plat' : 'Ajouter un nouveau plat';
    
    const modal = document.createElement('div');
    modal.className = 'modal modal-large';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>${title}</h3>
            <form id="plat-form">
                <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="nom">Nom du plat *</label>
                        <input type="text" id="nom" name="nom" required 
                               value="${plat?.nom || ''}" 
                               placeholder="Ex: Burger Classique, Coca-Cola...">
                    </div>
                    <div class="form-group">
                        <label for="prix">Prix (€) *</label>
                        <input type="number" id="prix" name="prix" step="0.01" min="0" required 
                               value="${plat?.prix || ''}">
                    </div>
                    <div class="form-group">
                        <label for="categorie">Catégorie *</label>
                        <select id="categorie" name="categorie" required>
                            <option value="plat" ${plat?.categorie === 'plat' ? 'selected' : ''}>Plat principal</option>
                            <option value="boisson" ${plat?.categorie === 'boisson' ? 'selected' : ''}>Boisson</option>
                            <option value="dessert" ${plat?.categorie === 'dessert' ? 'selected' : ''}>Dessert</option>
                            <option value="accompagnement" ${plat?.categorie === 'accompagnement' ? 'selected' : ''}>Accompagnement</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="Décrivez votre plat...">${plat?.description || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label for="ingredients">Ingrédients (description)</label>
                    <textarea id="ingredients" name="ingredients" rows="2" 
                              placeholder="Description des ingrédients pour les clients...">${plat?.ingredients || ''}</textarea>
                </div>
                
                <!-- Section ingrédients techniques -->
                <div class="form-group" style="border: 2px solid #e64a19; padding: 1rem; border-radius: 6px; background: #fff8f6;">
                    <h4 style="color: #e64a19; margin: 0 0 1rem 0;">Ingrédients & Partenariats commerciaux</h4>
                    <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                        Définissez les produits nécessaires à ce plat. Les produits "partenaires"(ex: Coca-Cola, Danone...) 
                        offriront des <strong>réductions individuelles</strong> aux clients pour booster les ventes.
                    </p>
                    
                    <div id="ingredients-techniques">
                        <div class="ingredient-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; align-items: end; margin-bottom: 0.5rem;">
                            <select class="produit-ingredient">
                                <option value="">Sélectionner un produit</option>
                            </select>
                            <input type="number" class="quantite-ingredient" placeholder="Quantité" step="0.001" min="0">
                            <select class="unite-ingredient">
                                <option value="kg">kg</option>
                                <option value="litres">litres</option>
                                <option value="unites">unités</option>
                            </select>
                            <button type="button" class="btn-action danger" onclick="retirerIngredient(this)">-</button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-secondary" onclick="ajouterIngredient()">+ Ajouter un ingrédient</button>
                    
                    <div id="ingredients-info" style="margin-top: 1rem; padding: 0.5rem; border-radius: 4px; font-size: 0.9rem; background: #f8f9fa; border: 1px solid #dee2e6;">
                        <!-- Info sur les partenariats sera affichée ici -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="allergenes">Allergènes</label>
                    <input type="text" id="allergenes" name="allergenes" 
                           value="${plat?.allergenes || ''}" 
                           placeholder="Ex: gluten, lactose, fruits à coque...">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Charger les produits disponibles
    loadProduitsForIngredients();
    
    // Si modification, charger les ingrédients existants
    if (isEdit && plat.id) {
        loadPlatIngredients(plat.id);
    }
    
    // Gérer la soumission du formulaire
    document.getElementById('plat-form').onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        // Récupérer les ingrédients techniques
        data.ingredients_techniques = collectIngredientsTechniques();
        
        if (isEdit) {
            data.id = plat.id;
            await updatePlat(data);
        } else {
            await addPlat(data);
        }
    };
}

/**
 * Charger les produits pour les ingrédients avec distinction partenaires
 */
async function loadProduitsForIngredients() {
    try {
        const response = await fetch('../api/produits/available.php');
        const produits = await response.json();
        
        const selects = document.querySelectorAll('.produit-ingredient');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Sélectionner un produit</option>';
            
            // Séparer les produits partenaires des autres
            const partenaires = produits.filter(p => p.obligatoire == 1);
            const standard = produits.filter(p => p.obligatoire == 0);
            
            if (partenaires.length > 0) {
                select.innerHTML += '<optgroup label="Produits partenaires (réductions client)">';
                partenaires.forEach(produit => {
                    select.innerHTML += `<option value="${produit.id}" data-obligatoire="1">⭐ ${produit.nom} (${produit.type})</option>`;
                });
                select.innerHTML += '</optgroup>';
            }
            
            if (standard.length > 0) {
                select.innerHTML += '<optgroup label="Produits standard">';
                standard.forEach(produit => {
                    select.innerHTML += `<option value="${produit.id}" data-obligatoire="0">${produit.nom} (${produit.type})</option>`;
                });
                select.innerHTML += '</optgroup>';
            }
        });
        
    } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
        const selects = document.querySelectorAll('.produit-ingredient');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Erreur de chargement des produits</option>';
        });
    }
}

/**
 * Ajouter un nouvel ingrédient
 */
function ajouterIngredient() {
    const container = document.getElementById('ingredients-techniques');
    const newItem = document.createElement('div');
    newItem.className = 'ingredient-item';
    newItem.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; align-items: end; margin-bottom: 0.5rem;';
    
    newItem.innerHTML = `
        <select class="produit-ingredient" onchange="updateIngredientsInfo()">
            <option value="">Sélectionner un produit</option>
        </select>
        <input type="number" class="quantite-ingredient" placeholder="Quantité" step="0.001" min="0" onchange="updateIngredientsInfo()">
        <select class="unite-ingredient">
            <option value="kg">kg</option>
            <option value="litres">litres</option>
            <option value="unites">unités</option>
        </select>
        <button type="button" class="btn-action danger" onclick="retirerIngredient(this)">-</button>
    `;
    
    container.appendChild(newItem);
    
    // Recharger les options de produits
    loadProduitsForIngredients();
}

/**
 * Retirer un ingrédient
 */
function retirerIngredient(button) {
    button.parentElement.remove();
    updateIngredientsInfo();
}

/**
 * Collecter les ingrédients techniques
 */
function collectIngredientsTechniques() {
    const ingredients = [];
    const items = document.querySelectorAll('.ingredient-item');
    
    items.forEach(item => {
        const produitId = item.querySelector('.produit-ingredient').value;
        const quantite = item.querySelector('.quantite-ingredient').value;
        const unite = item.querySelector('.unite-ingredient').value;
        
        if (produitId && quantite) {
            ingredients.push({
                produit_id: produitId,
                quantite_necessaire: parseFloat(quantite),
                unite: unite
            });
        }
    });
    
    return ingredients;
}

/**
 * Mettre à jour l'info des ingrédients avec la vraie logique métier
 */
function updateIngredientsInfo() {
    const ingredients = collectIngredientsTechniques();
    const infoDiv = document.getElementById('ingredients-info');
    
    if (ingredients.length === 0) {
        infoDiv.innerHTML = '<em style="color: #666;">Aucun ingrédient technique défini</em>';
        return;
    }
    
    let nbPartenaires = 0;
    let nbTotal = ingredients.length;
    let partenaires = [];
    
    ingredients.forEach(ingredient => {
        const select = document.querySelector(`[value="${ingredient.produit_id}"]`);
        if (select && select.dataset.obligatoire === '1') {
            nbPartenaires++;
            const produitNom = select.textContent.split(' (')[0].replace(' ⭐', '');
            partenaires.push(produitNom);
        }
    });
    
    infoDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>
                <strong>${nbTotal} ingrédient(s) défini(s)</strong> dont ${nbPartenaires} en partenariat
            </span>
            <span class="badge ${nbPartenaires > 0 ? 'badge-success' : 'badge-info'}">
                ${nbPartenaires > 0 ? `${nbPartenaires} réduction(s) partenaire` : 'Aucune réduction'}
            </span>
        </div>
        ${nbPartenaires > 0 ? `
            <div style="margin-top: 0.5rem; color: #28a745; font-size: 0.85rem;">
                ✅ <strong>Produits partenaires :</strong> ${partenaires.join(', ')}<br>
                <small>Les clients bénéficieront de réductions sur ces produits mis en avant</small>
            </div>
        ` : `
            <div style="margin-top: 0.5rem; color: #6c757d; font-size: 0.85rem;">
                Ajoutez des produits partenaires pour offrir des réductions à vos clients
            </div>
        `}
    `;
}

/**
 * Charger les ingrédients d'un plat existant
 */
async function loadPlatIngredients(menuId) {
    try {
        const response = await fetch(`../api/menu/produits.php?menu_id=${menuId}`);
        const ingredients = await response.json();
        
        if (Array.isArray(ingredients) && ingredients.length > 0) {
            const container = document.getElementById('ingredients-techniques');
            container.innerHTML = '';
            
            ingredients.forEach(ingredient => {
                ajouterIngredient();
                const lastItem = container.lastElementChild;
                
                lastItem.querySelector('.produit-ingredient').value = ingredient.produit_id;
                lastItem.querySelector('.quantite-ingredient').value = ingredient.quantite_necessaire;
                lastItem.querySelector('.unite-ingredient').value = ingredient.unite;
            });
            
            updateIngredientsInfo();
        }
        
    } catch (error) {
        console.error('Erreur lors du chargement des ingrédients:', error);
    }
}

/**
 * Ajouter un nouveau plat
 */
async function addPlat(data) {
        try {
        const response = await fetch('../api/plat/add.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Plat ajouté avec succès !', 'success');
            closeModal();
            loadMenus();
        } else {
            showAlert('Erreur : ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de l\'ajout:', error);
        showAlert('Erreur réseau lors de l\'ajout', 'error');
    }
}

/**
 * Modifier un plat existant
 */
async function updatePlat(data) {
    try {
        const response = await fetch('../api/plat/update.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Plat modifié avec succès !', 'success');
            closeModal();
            loadMenus();
        } else {
            showAlert('Erreur : ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la modification:', error);
        showAlert('Erreur réseau lors de la modification', 'error');
    }
}

/**
 * Modifier un plat
 */
function editPlat(id) {
    const plat = menus.find(p => p.id == id);
    if (plat) {
        showPlatModal(plat);
    }
}

/**
 * Supprimer un plat
 */
async function deletePlat(id) {
    const plat = menus.find(p => p.id == id);
    if (!plat) return;
    
    if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement "${plat.nom}" ?\n\nCette action est irréversible.`)) {
        return;
    }
    
    try {
        const response = await fetch('../api/plat/delete.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Plat supprimé avec succès !', 'success');
            loadMenus();
        } else {
            showAlert('Erreur : ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showAlert('Erreur réseau lors de la suppression', 'error');
    }
}

// FONCTIONS UTILITAIRES
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
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// GESTION DES ÉVÉNEMENTS GLOBAUX
// Fermer modal sur clic extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

// Gestion des touches clavier
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});