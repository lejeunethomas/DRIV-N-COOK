<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des camions - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body data-role="admin">
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php" class="active">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        
        <main class="main-content admin">
            <h1 class="admin">Gestion des camions</h1>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('camions', CamionManager.loadCamionsTab)">
                    Parc de camions
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('demandes', CamionManager.loadDemandesTab)">
                    Demandes à traiter <span class="badge" id="badge-demandes" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-camions" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0;">Parc de camions</h2>
                    <button class="add-btn" onclick="CamionManager.showAddModal()">+ Ajouter un camion</button>
                </div>
                
                <div id="camions-table-container"></div>
            </div>

            <div id="tab-demandes" class="tab-content">
                <h2>Demandes de camions à traiter</h2>
                <div id="demandes-table-container"></div>
            </div>
        </main>
    </div>
    
    <script src="../js/admin/common.js"></script>

    <script>
        // GESTIONNAIRE PRINCIPAL UNIFIÉ 
        const CamionManager = {
            // ===== DONNÉES =====
            data: {
                camions: [],
                demandes: [],
                franchises: []
            },

            // ===== CONFIGURATION CENTRALISÉE =====
            config: {
                endpoints: {
                    camions: '../api/camions/list.php',
                    demandes: '../api/camions/demandes.php',
                    franchises: '../api/users/get_all.php',
                    add: '../api/camions/add.php',
                    update: '../api/camions/update.php',
                    validate: '../api/camions/validate_demande.php',
                    syncEmplacement: '../api/camions/sync_emplacement.php'
                },
                
                headers: {
                    camions: ['Camion #', 'Franchisé', 'État', 'Emplacement', 'Prochaine maintenance', 'Demande maintenance', 'Date livraison', 'Actions'],
                    demandes: ['Franchisé', 'Nom du camion', 'N° Permis', 'Emplacement', 'Menu', 'Jours d\'ouverture', 'Date de demande', 'Actions']
                },
                
                etatsLabels: {
                    'en_preparation': 'En préparation',
                    'pret': 'Prêt',
                    'en_service': 'En service',
                    'maintenance': 'En maintenance',
                    'desactive': 'Désactivé'
                },
                
                formFields: {
                    add: [
                        { name: 'user_id', label: 'Franchisé *', type: 'select', required: true, options: 'getFranchiseOptions' },
                        { name: 'nom_camion', label: 'Nom du camion *', type: 'text', required: true, placeholder: 'Ex: Le Gourmand, Food Express' },
                        { name: 'etat', label: 'État', type: 'select', defaultValue: 'en_preparation', options: 'getEtatOptions' },
                        { name: 'date_livraison', label: 'Date de livraison', type: 'date', min: 'today' },
                        { name: 'emplacement', label: 'Emplacement', type: 'text', placeholder: 'Ex: Place de la République, Paris' },
                        { name: 'menu', label: 'Menu', type: 'textarea', rows: '3', placeholder: 'Description du menu proposé...' },
                        { name: 'jours', label: 'Jours d\'ouverture', type: 'text', placeholder: 'Ex: Lundi au Vendredi, Week-ends uniquement' }
                    ],
                    edit: [
                        { name: 'nom_camion', label: 'Nom du camion *', type: 'text', required: true },
                        { name: 'etat', label: 'État', type: 'select', options: 'getEtatOptions' },
                        { name: 'date_livraison', label: 'Date de livraison', type: 'date' },
                        { name: 'emplacement', label: 'Emplacement', type: 'text' },
                        { name: 'menu', label: 'Menu', type: 'textarea', rows: '3' },
                        { name: 'jours', label: 'Jours d\'ouverture', type: 'text' }
                    ]
                }
            },

            // ===== INITIALISATION =====
            async init() {
                await this.loadAllData();
                this.updateBadges();
            },

            async loadAllData() {
                await Promise.all([
                    this.loadCamionsTab(),
                    this.loadFranchises(),
                    this.loadDemandesTab()
                ]);
            },

            // ===== CHARGEMENT DONNÉES =====
            async loadCamionsTab() {
                try {
                    const response = await fetch(this.config.endpoints.camions);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    this.data.camions = await response.json();
                    console.log('✅ Camions chargés:', this.data.camions);
                    
                    if (!Array.isArray(this.data.camions)) {
                        console.warn('⚠️ Réponse camions non valide:', this.data.camions);
                        this.data.camions = [];
                    }
                    
                    this.displayCamions();
                } catch (error) {
                    console.error('❌ Erreur chargement camions:', error);
                    this.data.camions = [];
                    this.displayCamions();
                    this.showError('Erreur lors du chargement des camions: ' + error.message);
                }
            },

            async loadDemandesTab() {
                try {
                    const response = await fetch(this.config.endpoints.demandes);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    this.data.demandes = await response.json();
                    console.log('✅ Demandes chargées:', this.data.demandes);
                    
                    if (!Array.isArray(this.data.demandes)) {
                        console.warn('⚠️ Réponse demandes non valide:', this.data.demandes);
                        this.data.demandes = [];
                    }
                    
                    this.displayDemandes();
                    this.updateBadges();
                } catch (error) {
                    console.error('❌ Erreur chargement demandes:', error);
                    this.data.demandes = [];
                    this.displayDemandes();
                    this.updateBadges();
                    this.showError('Erreur lors du chargement des demandes: ' + error.message);
                }
            },

            async loadFranchises() {
                try {
                    const response = await fetch(this.config.endpoints.franchises);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    this.data.franchises = await response.json();
                    console.log('✅ Franchises chargées:', this.data.franchises);
                    
                    if (!Array.isArray(this.data.franchises)) {
                        console.warn('⚠️ Réponse franchises non valide:', this.data.franchises);
                        this.data.franchises = [];
                    }
                } catch (error) {
                    console.error('❌ Erreur chargement franchises:', error);
                    this.data.franchises = [];
                }
            },

            // ===== AFFICHAGE =====
            displayCamions() {
                console.log('📊 Affichage camions:', this.data.camions);

                if (!Array.isArray(this.data.camions)) {
                    console.warn('⚠️ Camions n\'est pas un tableau:', this.data.camions);
                    this.data.camions = [];
                }

                this.createTable({
                    containerId: 'camions-table-container',
                    headers: this.config.headers.camions,
                    data: this.data.camions,
                    rowBuilder: (camion) => {
                        const cells = this.buildCamionRow(camion);
                        return cells.map(cell => `<td>${cell}</td>`).join('');
                    },
                    emptyMessage: 'Aucun camion trouvé'
                });
            },

            displayDemandes() {
                console.log('📊 Affichage demandes:', this.data.demandes);
                
                if (!Array.isArray(this.data.demandes)) {
                    console.warn('⚠️ Demandes n\'est pas un tableau:', this.data.demandes);
                    this.data.demandes = [];
                }

                this.createTable({
                    containerId: 'demandes-table-container',
                    headers: this.config.headers.demandes,
                    data: this.data.demandes,
                    rowBuilder: (demande) => {
                        const cells = this.buildDemandeRow(demande);
                        return cells.map(cell => `<td>${cell}</td>`).join('');
                    },
                    emptyMessage: 'Aucune demande en attente'
                });
            },

            // ===== UTILITAIRES =====
            createTable(config) {
                const container = document.getElementById(config.containerId);
                
                if (!container) {
                    console.error(`Container ${config.containerId} non trouvé`);
                    return;
                }

                if (!config.data || config.data.length === 0) {
                    container.innerHTML = `<p style="text-align: center; color: #666; padding: 2rem;">${config.emptyMessage}</p>`;
                    return;
                }

                const table = document.createElement('table');
                table.innerHTML = `
                    <thead>
                        <tr>${config.headers.map(h => `<th>${h}</th>`).join('')}</tr>
                    </thead>
                    <tbody>
                        ${config.data.map(item => `<tr>${config.rowBuilder(item)}</tr>`).join('')}
                    </tbody>
                `;

                container.innerHTML = '';
                container.appendChild(table);
            },

            showError(message) {
                console.error('💥', message);
                
                const alertDiv = document.createElement('div');
                alertDiv.style.cssText = `
                    position: fixed; top: 20px; right: 20px; z-index: 9999;
                    background: #f44336; color: white; padding: 1rem;
                    border-radius: 6px; max-width: 300px;
                `;
                alertDiv.textContent = message;
                
                document.body.appendChild(alertDiv);
                
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 5000);
            },

            updateBadges() {
                const badgeElement = document.getElementById('badge-demandes');
                if (!badgeElement) return;
                
                const count = this.data.demandes?.length || 0;
                
                if (count > 0) {
                    badgeElement.textContent = count;
                    badgeElement.style.display = 'inline';
                } else {
                    badgeElement.style.display = 'none';
                }
            },

            // ===== CONSTRUCTEURS DE LIGNES =====
            buildCamionRow(camion) {
                const franchiseNom = camion.franchise_nom && camion.franchise_prenom ? 
                    `${camion.franchise_nom} ${camion.franchise_prenom}` : 
                    'Non assigné';

                return [
                    camion.id || '-',
                    franchiseNom,
                    `<span class="etat-badge etat-${camion.etat}">${this.getEtatLabel(camion.etat)}</span>`,
                    camion.emplacement || 'Non renseigné',
                    camion.prochaine_maintenance || '-',
                    this.getMaintenanceBadge(camion.demande_maintenance),
                    this.getLivraisonInfo(camion.date_livraison),
                    `<button class="btn-action" onclick="CamionManager.edit(${camion.id})">Modifier</button>
                     <button class="btn-action warning" onclick="CamionManager.synchroniserEmplacement(${camion.id})">Sync lieu</button>
                     <button class="btn-action warning" onclick="CamionManager.planifierMaintenance(${camion.id})">Maintenance</button>`
                ];
            },

            buildDemandeRow(demande) {
                return [
                    `<strong>${demande.franchise_nom || ''} ${demande.franchise_prenom || ''}</strong><br><small>${demande.franchise_email || ''}</small>`,
                    `<strong>${demande.nom_camion || ''}</strong>`,
                    demande.numero_permis || 'Non renseigné',
                    demande.emplacement || 'Non renseigné',
                    `<span title="${demande.menu || ''}">${this.truncateText(demande.menu, 30)}</span>`,
                    demande.jours || 'Non renseigné',
                    this.formatDate(demande.date_demande),
                    `<button class="btn-action success" onclick="CamionManager.validerDemande(${demande.id})">Valider</button>
                     <button class="btn-action danger" onclick="CamionManager.refuserDemande(${demande.id})">Refuser</button>`
                ];
            },

            // ===== MODALS MANUELLES =====
            showAddModal() {
                this.createCamionModal('Ajouter un camion manuellement', null, this.handleAdd.bind(this));
            },

            edit(camionId) {
                const camion = this.data.camions.find(c => c.id == camionId);
                if (!camion) {
                    alert('Camion non trouvé');
                    return;
                }
                
                console.log('🔧 Modification du camion:', camion);
                this.createCamionModal(`Modifier le camion #${camion.id}`, camion, this.handleEdit.bind(this, camionId));
            },

            createCamionModal(title, data, onSubmit) {
                const isEdit = !!data;
                const fields = isEdit ? this.config.formFields.edit : this.config.formFields.add;
                
                const modal = document.createElement('div');
                modal.className = 'modal';
                
                const fieldsHTML = fields.map(field => this.buildFormField(field, data)).join('');

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${title}</h3>
                        ${isEdit ? `<div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong>Camion :</strong> ${data.nom_camion}<br>
                            <strong>Franchisé :</strong> ${data.franchise_nom || ''} ${data.franchise_prenom || ''}<br>
                            <strong>Immatriculation :</strong> ${data.immatriculation || 'N/A'}
                        </div>` : ''}
                        <form id="camion-modal-form">
                            ${fieldsHTML}
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="CamionManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('camion-modal-form').addEventListener('submit', onSubmit);
            },

            buildFormField(field, data) {
                const value = data ? (data[field.name] || '') : (field.defaultValue || '');
                const id = `camion-${field.name}`;
                
                console.log(`🏗️ Field ${field.name}:`, { value, data: data?.[field.name] });
                
                const attributes = [];
                if (field.required) attributes.push('required');
                if (field.placeholder) attributes.push(`placeholder="${field.placeholder}"`);
                if (field.min === 'today') attributes.push(`min="${new Date().toISOString().split('T')[0]}"`);
                if (field.rows) attributes.push(`rows="${field.rows}"`);
                
                if (field.type === 'select') {
                    const options = this.getSelectOptions(field.options);
                    const optionsHTML = options.map(opt => 
                        `<option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>${opt.text}</option>`
                    ).join('');
                    
                    return `
                        <div class="form-group">
                            <label for="${id}">${field.label}</label>
                            <select id="${id}" name="${field.name}" ${attributes.join(' ')}>
                                ${optionsHTML}
                            </select>
                        </div>
                    `;
                } else if (field.type === 'textarea') {
                    return `
                        <div class="form-group">
                            <label for="${id}">${field.label}</label>
                            <textarea id="${id}" name="${field.name}" ${attributes.join(' ')}>${value}</textarea>
                        </div>
                    `;
                } else {
                    return `
                        <div class="form-group">
                            <label for="${id}">${field.label}</label>
                            <input type="${field.type}" id="${id}" name="${field.name}" value="${value}" ${attributes.join(' ')}>
                        </div>
                    `;
                }
            },

            // ===== HANDLERS =====
            async handleAdd(e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                
                console.log('➕ Données ajout camion:', data);
                
                if (!this.validateCamionData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.add, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📋 Résultat ajout:', result);

                    if (result.success) {
                        const message = `✅ ${result.message}\nImmatriculation: ${result.immatriculation || 'Générée'}`;
                        alert(message);
                        this.closeModal();
                        await this.loadCamionsTab();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    console.error('❌ Erreur ajout:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            async handleEdit(camionId, e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));
                data.id = camionId;
                
                console.log('🔧 Données modification camion:', data);
                
                if (!this.validateCamionData(data, true)) return;

                try {
                    const response = await fetch(this.config.endpoints.update, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📋 Résultat modification:', result);

                    if (result.success) {
                        alert('✅ Camion modifié avec succès !');
                        this.closeModal();
                        await this.loadCamionsTab();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    console.error('❌ Erreur modification:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            // ===== FONCTIONS SPÉCIALISÉES DEMANDES =====
            validerDemande(demandeId) {
                const demande = this.data.demandes.find(d => d.id == demandeId);
                if (!demande) {
                    alert('Demande non trouvée');
                    return;
                }

                this.createDemandeModal('Valider la demande de camion', demande, 'valider', (data) => {
                    return { demande_id: demandeId, action: 'valider', ...data };
                });
            },

            refuserDemande(demandeId) {
                const demande = this.data.demandes.find(d => d.id == demandeId);
                if (!demande) {
                    alert('Demande non trouvée');
                    return;
                }

                this.createDemandeModal('Refuser la demande de camion', demande, 'refuser', (data) => {
                    return { demande_id: demandeId, action: 'refuser', ...data };
                });
            },

            createDemandeModal(title, demande, action, dataBuilder) {
                const modal = document.createElement('div');
                modal.className = 'modal';

                const infoHTML = this.buildDemandeInfo(demande);
                const formHTML = action === 'valider' ? this.buildValidateForm() : this.buildRefuseForm();

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>${title}</h3>
                        ${infoHTML}
                        <form id="demande-modal-form">
                            ${formHTML}
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="CamionManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary ${action === 'refuser' ? 'btn-action danger' : ''}">${action === 'valider' ? 'Valider la demande' : 'Refuser définitivement'}</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('demande-modal-form').addEventListener('submit', 
                    this.handleDemandeSubmit.bind(this, dataBuilder)
                );
            },

            buildDemandeInfo(demande) {
                return `
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <strong>Franchisé :</strong> ${demande.franchise_nom} ${demande.franchise_prenom}<br>
                        <strong>Email :</strong> ${demande.franchise_email}<br>
                        <strong>Camion demandé :</strong> ${demande.nom_camion}<br>
                        <strong>Emplacement :</strong> ${demande.emplacement}<br>
                        <strong>Menu :</strong> ${AdminCommon.utils.truncateText(demande.menu, 100)}<br>
                        <strong>Jours :</strong> ${demande.jours}
                    </div>
                `;
            },

            buildValidateForm() {
                const defaultDate = new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0];
                return `
                    <div class="form-group">
                        <label for="validate-date-livraison">Date de livraison prévue *</label>
                        <input type="date" id="validate-date-livraison" name="date_livraison" required 
                               min="${new Date().toISOString().split('T')[0]}"
                               value="${defaultDate}">
                        <small style="color:#666;">Par défaut : dans 2 semaines</small>
                    </div>
                    <div class="form-group">
                        <label for="validate-commentaire">Commentaire (optionnel)</label>
                        <textarea id="validate-commentaire" name="commentaire" rows="3" 
                                  placeholder="Instructions pour le franchisé, notes particulières..."></textarea>
                    </div>
                `;
            },

            buildRefuseForm() {
                return `
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                        <strong>⚠️ Attention :</strong> Cette action va refuser définitivement la demande de camion.
                    </div>
                    <div class="form-group">
                        <label for="refuse-commentaire">Raison du refus *</label>
                        <textarea id="refuse-commentaire" name="commentaire" rows="4" required
                                  placeholder="Expliquez pourquoi cette demande est refusée (ex: documentation incomplète, critères non respectés, etc.)"></textarea>
                    </div>
                `;
            },

            async handleDemandeSubmit(dataBuilder, e) {
                e.preventDefault();
                const formData = Object.fromEntries(new FormData(e.target));
                const data = dataBuilder(formData);

                if (!this.validateDemandeData(data)) return;

                try {
                    const response = await fetch(this.config.endpoints.validate, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ ' + result.message);
                        this.closeModal();
                        await Promise.all([
                            this.loadDemandesTab(),
                            this.loadCamionsTab()
                        ]);
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            // ===== FONCTIONS UTILITAIRES =====
            synchroniserEmplacement(camionId) {
                const camion = this.data.camions.find(c => c.id == camionId);
                if (!camion) {
                    alert('Camion non trouvé');
                    return;
                }

                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>Synchroniser l'emplacement</h3>
                        
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong>Camion :</strong> ${camion.nom_camion}<br>
                            <strong>Franchisé :</strong> ${camion.franchise_nom}<br>
                            <strong>Emplacement actuel du camion :</strong> ${camion.emplacement || 'Non défini'}<br>
                            <strong>Lieu d'installation du franchisé :</strong> ${camion.lieu_installation || 'Non défini'}
                        </div>
                        
                        <form id="sync-emplacement-form">
                            <div class="form-group">
                                <label for="sync-emplacement">Nouvel emplacement unifié *</label>
                                <input type="text" id="sync-emplacement" name="emplacement" 
                                       value="${camion.emplacement || camion.lieu_installation || ''}" 
                                       required placeholder="Ex: Place de la République, Paris">
                                <small style="color:#666;">Cet emplacement sera appliqué au camion ET au franchisé</small>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="CamionManager.closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">Synchroniser</button>
                            </div>
                        </form>
                    </div>
                `;

                document.body.appendChild(modal);
                document.getElementById('sync-emplacement-form').addEventListener('submit', 
                    this.handleSyncEmplacement.bind(this, camionId)
                );
            },

            async handleSyncEmplacement(camionId, e) {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.target));

                if (!data.emplacement) {
                    alert('❌ Veuillez saisir un emplacement');
                    return;
                }

                try {
                    const response = await fetch(this.config.endpoints.syncEmplacement, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            camion_id: camionId,
                            emplacement: data.emplacement
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Emplacement synchronisé avec succès !');
                        this.closeModal();
                        await Promise.all([
                            this.loadCamionsTab(),
                            this.loadFranchises()
                        ]);
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }
                } catch (error) {
                    alert('❌ Erreur réseau: ' + error.message);
                }
            },

            planifierMaintenance(camionId) {
                AdminCommon.utils.showAlert(`Planification de maintenance pour le camion #${camionId} - Fonctionnalité à développer`, 'info');
            },

            // ===== HELPERS =====
            formatDate(dateString) {
                if (!dateString) return '-';
                try {
                    return new Date(dateString).toLocaleDateString('fr-FR');
                } catch {
                    return dateString;
                }
            },

            truncateText(text, maxLength) {
                if (!text) return '';
                return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
            },

            getFranchiseOptions() {
                if (!Array.isArray(this.data.franchises)) {
                    console.warn('⚠️ Franchises non chargées');
                    return [{ value: '', text: 'Chargement des franchisés...' }];
                }

                return [
                    { value: '', text: 'Sélectionner un franchisé' },
                    ...this.data.franchises
                        .filter(f => f.role === 'franchise' && f.statut === 'valide')
                        .map(f => ({
                            value: f.id,
                            text: `${f.nom || ''} ${f.prenom || ''} (${f.email || ''})`
                        }))
                ];
            },

            getEtatOptions() {
                return Object.entries(this.config.etatsLabels).map(([value, text]) => ({
                    value,
                    text
                }));
            },

            getEtatLabel(etat) {
                return this.config.etatsLabels[etat] || etat;
            },

            getMaintenanceBadge(demandeMaintenance) {
                return demandeMaintenance ? 
                    '<span class="badge-danger">À traiter</span>' : 
                    '<span class="badge-success">OK</span>';
            },

            getLivraisonInfo(dateLivraison) {
                return dateLivraison ? 
                    `<span class="date-livraison">${this.formatDate(dateLivraison)}</span>` : 
                    '-';
            },

            updateBadges() {
                const badgeElement = document.getElementById('badge-demandes');
                if (!badgeElement) return;
                
                const count = this.data.demandes?.length || 0;
                
                if (count > 0) {
                    badgeElement.textContent = count;
                    badgeElement.style.display = 'inline';
                } else {
                    badgeElement.style.display = 'none';
                }
            },

            // ===== VALIDATIONS =====
            validateCamionData(data, isEdit = false) {
                console.log('🔍 Validation camion:', { data, isEdit });
                
                if (isEdit) {
                    if (!data.nom_camion || data.nom_camion.trim() === '') {
                        alert('❌ Le nom du camion est obligatoire');
                        return false;
                    }
                } else {
                    if (!data.user_id || !data.nom_camion || data.nom_camion.trim() === '') {
                        alert('❌ Veuillez remplir tous les champs obligatoires');
                        return false;
                    }
                }
                
                return true;
            },

            validateDemandeData(data) {
                if (data.action === 'valider' && !data.date_livraison) {
                    alert('❌ La date de livraison est obligatoire');
                    return false;
                }
                
                if (data.action === 'refuser' && (!data.commentaire || data.commentaire.trim().length < 10)) {
                    alert('❌ Veuillez expliquer la raison du refus (minimum 10 caractères)');
                    return false;
                }
                
                return true;
            },

            closeModal() {
                const modal = document.querySelector('.modal');
                if (modal) modal.remove();
            },

            getSelectOptions(optionsSource) {
                if (typeof optionsSource === 'string' && typeof this[optionsSource] === 'function') {
                    return this[optionsSource]();
                }
                return Array.isArray(optionsSource) ? optionsSource : [];
            },
        };

        // ===== INITIALISATION AUTOMATIQUE =====
        document.addEventListener('DOMContentLoaded', function() {
            CamionManager.init();
        });

        // ===== FONCTIONS GLOBALES =====
        function loadCamionsTab() { return CamionManager.loadCamionsTab(); }
        function loadDemandesTab() { return CamionManager.loadDemandesTab(); }
        function showAddCamionModal() { CamionManager.showAddModal(); }
        function editCamion(id) { CamionManager.edit(id); }
        function validerDemande(id) { CamionManager.validerDemande(id); }
        function refuserDemande(id) { CamionManager.refuserDemande(id); }
        function synchroniserEmplacement(id) { CamionManager.synchroniserEmplacement(id); }
        function planifierMaintenance(id) { CamionManager.planifierMaintenance(id); }
        function updateBadges() { CamionManager.updateBadges(); }
    </script>
</body>
</html>