// VARIABLES GLOBALES PARTAGÉES

window.AdminCommon = {
    data: {
        currentUser: null,
        notifications: []
    },
    
    config: {
        endpoints: {
            base: '../api/',
            users: '../api/users/',
            produits: '../api/produits/',
            entrepots: '../api/entrepots/',
            stocks: '../api/stocks/',
            camions: '../api/camions/',
            commandes: '../api/commandes/',
            ventes: '../api/ventes/'
        },
        
        statusClasses: {
            success: 'badge-success',
            error: 'badge-danger',
            warning: 'badge-warning',
            info: 'badge-info',
            secondary: 'badge-secondary'
        },
        
        alertTypes: {
            success: { class: 'alert-success', duration: 3000 },
            error: { class: 'alert-error', duration: 5000 },
            warning: { class: 'alert-warning', duration: 4000 },
            info: { class: 'alert-info', duration: 3000 }
        }
    }
};

// FONCTIONS UTILITAIRES DE BASE

/**
 * Formater un prix avec devise
 */
function formatPrice(price) {
    return parseFloat(price || 0).toFixed(2) + '€';
}

/**
 * Formater une date française
 */
function formatDate(dateString, withTime = false) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    };
    
    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleDateString('fr-FR', options);
}

/**
 * Tronquer un texte avec ellipses
 */
function truncateText(text, maxLength = 50) {
    if (!text) return 'Non renseigné';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

/**
 * Générer un ID unique
 */
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

/**
 * Debouncer une fonction
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// GESTION DES ALERTES CENTRALISÉE

/**
 * Afficher une alerte unifiée
 */
function showAlert(message, type = 'info', duration = null) {
    const config = AdminCommon.config.alertTypes[type] || AdminCommon.config.alertTypes.info;
    const alertDuration = duration || config.duration;
    
    // Chercher le conteneur d'alertes ou en créer un
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(alertContainer);
    }
    
    const alertId = generateId();
    const alert = document.createElement('div');
    alert.id = `alert-${alertId}`;
    alert.className = `alert ${config.class}`;
    alert.style.cssText = `
        margin-bottom: 0.5rem;
        padding: 1rem 1.5rem;
        border-radius: 6px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        animation: slideInRight 0.3s ease;
        position: relative;
        cursor: pointer;
    `;
    
    alert.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button onclick="closeAlert('${alertId}')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">×</button>
        </div>
    `;
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => closeAlert(alertId), alertDuration);
    
    alert.addEventListener('click', () => closeAlert(alertId));
}

/**
 * Fermer une alerte spécifique
 */
function closeAlert(alertId) {
    const alert = document.getElementById(`alert-${alertId}`);
    if (alert) {
        alert.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }
}

// GESTION DES MODALS CENTRALISÉE

/**
 * Créer une modal générique et réutilisable
 */
function createModal(config) {
    const {
        title,
        content,
        size = 'normal',
        actions = [],
        closable = true,
        onOpen = null,
        onClose = null
    } = config;
    
    const modalId = generateId();
    const sizeClasses = {
        normal: '',
        large: 'modal-large',
        'extra-large': 'modal-extra-large'
    };
    
    const modal = document.createElement('div');
    modal.id = `modal-${modalId}`;
    modal.className = `modal ${sizeClasses[size]}`;
    
    const actionsHTML = actions.map(action => {
        const btnClass = action.type === 'primary' ? 'btn-primary' : 
                        action.type === 'secondary' ? 'btn-secondary' : 
                        action.type === 'danger' ? 'btn-action danger' : 
                        action.type === 'success' ? 'btn-action success' : 'btn-action';
        
        return `<button type="${action.buttonType || 'button'}" 
                        class="${btnClass}" 
                        onclick="${action.onclick || ''}"
                        ${action.disabled ? 'disabled' : ''}>${action.text}</button>`;
    }).join('');
    
    modal.innerHTML = `
        <div class="modal-content">
            ${title ? `<h3>${title}</h3>` : ''}
            <div class="modal-body">${content}</div>
            <div class="modal-actions">
                ${actionsHTML}
                ${closable && !actions.some(a => a.text === 'Fermer') ? 
                  '<button type="button" class="btn-secondary" onclick="closeModal()">Fermer</button>' : ''}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    if (onOpen) onOpen(modalId);

    modal.onCloseCallback = onClose;
    
    return modalId;
}

/**
 * Fermer toutes les modals ou une modal spécifique
 */
function closeModal(modalId = null) {
    const modals = modalId ? 
        [document.getElementById(`modal-${modalId}`)] : 
        document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        if (modal) {
            if (modal.onCloseCallback) modal.onCloseCallback();
            modal.remove();
        }
    });
}

// GESTION DES REQUÊTES API CENTRALISÉE

/**
 * Effectuer une requête API avec gestion d'erreurs unifiée
 */
async function apiRequest(endpoint, options = {}) {
    const {
        method = 'GET',
        data = null,
        headers = {},
        showLoader = false,
        errorMessage = 'Erreur lors de la requête'
    } = options;
    
    const requestOptions = {
        method,
        headers: {
            'Content-Type': 'application/json',
            ...headers
        }
    };
    
    if (data && ['POST','PUT','DELETE','PATCH'].includes(method)) {
        try { requestOptions.body = JSON.stringify(data); }
        catch(e){ console.warn('Serialize fail', e); }
    }
    
    if (method === 'DELETE' && !requestOptions.body && data && typeof data === 'object') {
        const qs = new URLSearchParams(data).toString();
        endpoint += (endpoint.includes('?') ? '&' : '?') + qs;
    }
    
    if (showLoader) showAlert('Chargement...', 'info', 1000);
    
    try {
        console.log(`[API] ${method} ${endpoint}`, data ? { data } : '');
        
        const response = await fetch(endpoint, requestOptions);
        
        console.log(`[API] Status: ${response.status}, Headers:`, [...response.headers.entries()]);
        
        const rawResponse = await response.text();
        console.log(`[API] Raw response (${rawResponse.length} chars):`, rawResponse.substring(0, 200));
        
        if (!rawResponse || rawResponse.trim() === '') {
            throw new Error(`API ${endpoint} a retourné une réponse vide`);
        }
        
        const contentType = response.headers.get('content-type') || 'non défini';
        if (!contentType.includes('application/json')) {
            throw new Error(`API ${endpoint} a retourné du "${contentType}" au lieu de JSON. Réponse: ${rawResponse.substring(0, 300)}`);
        }
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}: ${rawResponse.substring(0, 200)}`);
        }
        
        let result;
        try {
            result = JSON.parse(rawResponse);
        } catch (parseError) {
            throw new Error(`JSON invalide de ${endpoint}: ${parseError.message}. Contenu: ${rawResponse.substring(0, 200)}`);
        }
        
        console.log(`[API] ✅ Succès:`, result);
        return result;
        
    } catch (error) {
        console.error(`[API] ❌ Erreur ${method} ${endpoint}:`, error);
        showAlert(`${errorMessage}: ${error.message}`, 'error');
        throw error;
    }
}

/**
 * Sauvegarder des données (POST/PUT automatique)
 */
async function saveData(endpoint, data, isEdit = false) {
    const method = isEdit ? 'PUT' : 'POST';
    const action = isEdit ? 'modifié' : 'ajouté';
    
    try {
        const result = await apiRequest(endpoint, {
            method,
            data,
            showLoader: true
        });
        
        if (result.success) {
            showAlert(`Élément ${action} avec succès !`, 'success');
            return result;
        } else {
            showAlert(`Erreur : ${result.message}`, 'error');
            return null;
        }
    } catch (error) {
        return null;
    }
}

/**
 * Supprimer des données avec confirmation
 */
async function deleteData(endpoint, data, confirmMessage) {
    if (!confirm(confirmMessage)) return false;
    
    try {
        const result = await apiRequest(endpoint, {
            method: 'DELETE',
            data,
            showLoader: true
        });
        
        if (result.success) {
            showAlert('Élément supprimé avec succès !', 'success');
            return true;
        } else {
            showAlert(`Erreur : ${result.message}`, 'error');
            return false;
        }
    } catch (error) {
        return false;
    }
}

// GESTION DES TABLEAUX CENTRALISÉE

/**
 * Créer un tableau avec configuration
 */
function createTable(config) {
    const {
        containerId,
        headers,
        data,
        rowBuilder,
        emptyMessage = 'Aucune donnée disponible',
        sortable = false
    } = config;
    
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const table = document.createElement('table');
    table.innerHTML = `
        <thead>
            <tr>
                ${headers.map(header => `<th${sortable ? ' style="cursor: pointer;"' : ''}>${header}</th>`).join('')}
            </tr>
        </thead>
        <tbody></tbody>
    `;
    
    const tbody = table.querySelector('tbody');
    
    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${headers.length}" style="text-align: center; color: #666; padding: 2rem;">
                    ${emptyMessage}
                </td>
            </tr>
        `;
    } else {
        data.forEach(item => {
            const row = rowBuilder(item);
            tbody.appendChild(row);
        });
    }
    
    container.innerHTML = '';
    container.appendChild(table);
    
    return table;
}

// GESTION DES ONGLETS CENTRALISÉE

/**
 * Gérer les onglets de manière unifiée
 */
function switchTab(tabId, onSwitch = null) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const targetTab = document.getElementById(`tab-${tabId}`);
    const targetBtn = event?.target;
    
    if (targetTab) targetTab.classList.add('active');
    if (targetBtn) targetBtn.classList.add('active');
    
    if (onSwitch) onSwitch(tabId);
}

/**
 * Mettre à jour les badges des onglets
 */
function updateTabBadge(tabId, count) {
    const badge = document.getElementById(`badge-${tabId}`);
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

// FONCTIONS D'ANIMATION ET UI

/**
 * Ajouter des animations CSS
 */
function addAnimations() {
    if (document.getElementById('admin-common-animations')) return;
    
    const style = document.createElement('style');
    style.id = 'admin-common-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading { animation: pulse 1.5s infinite; }
        .fade-in { animation: fadeIn 0.3s ease; }
    `;
    
    document.head.appendChild(style);
}

// GESTION DES ÉVÉNEMENTS GLOBAUX

document.addEventListener('DOMContentLoaded', function() {
    addAnimations();
    
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
});

// EXPORT DES FONCTIONS

// Pour les modules ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatPrice,
        formatDate,
        truncateText,
        generateId,
        debounce,
        
        showAlert,
        closeAlert,
        
        createModal,
        closeModal,
        
        apiRequest,
        saveData,
        deleteData,
        
        createTable,
        
        switchTab,
        updateTabBadge,
        
        AdminCommon
    };
}

// Rendre disponible globalement
window.AdminCommon.utils = {
    formatPrice,
    formatDate,
    truncateText,
    generateId,
    debounce,
    showAlert,
    closeAlert,
    createModal,
    closeModal,
    apiRequest,
    saveData,
    deleteData,
    createTable,
    switchTab,
    updateTabBadge
};