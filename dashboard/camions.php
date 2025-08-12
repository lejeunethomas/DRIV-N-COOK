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
<body>
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
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('camions', loadCamionsTab)">
                    Parc de camions
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('demandes', loadDemandesTab)">
                    Demandes à traiter <span class="badge" id="badge-demandes" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-camions" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0;">Parc de camions</h2>
                    <button class="add-btn" onclick="showAddCamionModal()">+ Ajouter un camion</button>
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
        const camionManager = {
            data: { camions: [], demandes: [], franchises: [] },
            
            config: {
                endpoints: {
                    camions: '../api/camions/list.php',
                    demandes: '../api/camions/demandes.php',
                    franchises: '../api/users/get_all.php',
                    add: '../api/camions/add.php',
                    update: '../api/camions/update.php',
                    validate: '../api/camions/validate_demande.php'
                },
                
                tables: {
                    camions: {
                        headers: ['Camion #', 'Franchisé', 'État', 'Emplacement', 'Prochaine maintenance', 'Demande maintenance', 'Date livraison', 'Actions'],
                        rowBuilder: (camion) => {
                            const urgence = camion.demande_maintenance ? 'camion-urgence' : '';
                            
                            return [
                                camion.id || '-',
                                camion.franchise_nom || 'Non assigné',
                                `<span class="etat-badge etat-${camion.etat}">${getEtatLabel(camion.etat)}</span>`,
                                camion.emplacement || 'Non renseigné',
                                camion.prochaine_maintenance || '-',
                                getMaintenanceBadge(camion.demande_maintenance),
                                getLivraisonInfo(camion.date_livraison),
                                `<button class="btn-action" onclick="editCamion(${camion.id})">Modifier</button>
                                 <button class="btn-action warning" onclick="synchroniserEmplacement(${camion.id})">Sync lieu</button>
                                 <button class="btn-action warning" onclick="planifierMaintenance(${camion.id})">Maintenance</button>`,
                                urgence
                            ];
                        }
                    },
                    
                    demandes: {
                        headers: ['Franchisé', 'Nom du camion', 'N° Permis', 'Emplacement', 'Menu', 'Jours d\'ouverture', 'Date de demande', 'Actions'],
                        rowBuilder: (demande) => [
                            `<strong>${demande.franchise_nom || ''} ${demande.franchise_prenom || ''}</strong><br><small>${demande.franchise_email || ''}</small>`,
                            `<strong>${demande.nom_camion || ''}</strong>`,
                            demande.numero_permis || 'Non renseigné',
                            demande.emplacement || 'Non renseigné',
                            `<span title="${demande.menu || ''}">${AdminCommon.utils.truncateText(demande.menu, 30)}</span>`,
                            demande.jours || 'Non renseigné',
                            AdminCommon.utils.formatDate(demande.date_demande),
                            `<button class="btn-action success" onclick="validerDemande(${demande.id})">Valider</button>
                             <button class="btn-action danger" onclick="refuserDemande(${demande.id})">Refuser</button>`
                        ]
                    }
                },
                
                etatsLabels: {
                    'en_preparation': 'En préparation',
                    'pret': 'Prêt',
                    'en_service': 'En service',
                    'maintenance': 'En maintenance',
                    'desactive': 'Désactivé'
                },
                
                formFields: {
                    camionAdd: [
                        { name: 'user_id', label: 'Franchisé', type: 'select', required: true, options: 'getFranchiseOptions' },
                        { name: 'nom_camion', label: 'Nom du camion', type: 'text', required: true, attributes: 'placeholder="Ex: Le Gourmand, Food Express"' },
                        { name: 'etat', label: 'État', type: 'select', defaultValue: 'en_preparation', options: 'getEtatOptions' },
                        { name: 'date_livraison', label: 'Date de livraison', type: 'date', attributes: `min="${new Date().toISOString().split('T')[0]}"` },
                        { name: 'emplacement', label: 'Emplacement', type: 'text', attributes: 'placeholder="Ex: Place de la République, Paris"' },
                        { name: 'menu', label: 'Menu', type: 'textarea', attributes: 'rows="3" placeholder="Description du menu proposé..."' },
                        { name: 'jours', label: 'Jours d\'ouverture', type: 'text', attributes: 'placeholder="Ex: Lundi au Vendredi, Week-ends uniquement"' }
                    ],
                    
                    camionEdit: [
                        { name: 'nom_camion', label: 'Nom du camion', type: 'text', required: true },
                        { name: 'etat', label: 'État', type: 'select', options: 'getEtatOptions' },
                        { name: 'date_livraison', label: 'Date de livraison', type: 'date' },
                        { name: 'emplacement', label: 'Emplacement', type: 'text' },
                        { name: 'menu', label: 'Menu', type: 'textarea', attributes: 'rows="3"' },
                        { name: 'jours', label: 'Jours d\'ouverture', type: 'text' }
                    ]
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadAllData();
        });

        async function loadAllData() {
            await Promise.all([
                loadCamionsTab(),
                loadFranchises(),
                loadDemandesTab()
            ]);
            updateBadges();
        }

        async function loadCamionsTab() {
            try {
                camionManager.data.camions = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.camions);
                displayCamions();
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des camions', 'error');
            }
        }

        async function loadDemandesTab() {
            try {
                camionManager.data.demandes = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.demandes);
                displayDemandes();
                AdminCommon.utils.updateTabBadge('demandes', camionManager.data.demandes.length);
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des demandes', 'error');
            }
        }

        async function loadFranchises() {
            try {
                camionManager.data.franchises = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.franchises);
            } catch (error) {
                console.error('Erreur lors du chargement des franchisés');
            }
        }

        function displayCamions() {
            AdminCommon.utils.createTable({
                containerId: 'camions-table-container',
                headers: camionManager.config.tables.camions.headers,
                data: camionManager.data.camions,
                rowBuilder: (camion) => {
                    const row = document.createElement('tr');
                    const cellsData = camionManager.config.tables.camions.rowBuilder(camion);
                    const rowClass = cellsData[cellsData.length - 1];
                    const cells = cellsData.slice(0, -1);
                    
                    // ✅ AJOUTER bouton synchronisation
                    const actionsIndex = cells.length - 1;
                    cells[actionsIndex] = `
                        <button class="btn-action" onclick="editCamion(${camion.id})">Modifier</button>
                        <button class="btn-action warning" onclick="synchroniserEmplacement(${camion.id})">Sync lieu</button>
                        <button class="btn-action warning" onclick="planifierMaintenance(${camion.id})">Maintenance</button>
                    `;
                    
                    if (rowClass) row.className = rowClass;
                    row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                    return row;
                },
                emptyMessage: 'Aucun camion trouvé'
            });
        }

        function displayDemandes() {
            AdminCommon.utils.createTable({
                containerId: 'demandes-table-container',
                headers: camionManager.config.tables.demandes.headers,
                data: camionManager.data.demandes,
                rowBuilder: (demande) => {
                    const row = document.createElement('tr');
                    const cells = camionManager.config.tables.demandes.rowBuilder(demande);
                    row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                    return row;
                },
                emptyMessage: 'Aucune demande en attente'
            });
        }

        function getEtatLabel(etat) {
            return camionManager.config.etatsLabels[etat] || etat;
        }

        function getMaintenanceBadge(demandeMaintenance) {
            return demandeMaintenance ? 
                '<span class="badge-danger">À traiter</span>' : 
                '<span class="badge-success">OK</span>';
        }

        function getLivraisonInfo(dateLivraison) {
            return dateLivraison ? 
                `<span class="date-livraison">${AdminCommon.utils.formatDate(dateLivraison)}</span>` : 
                '-';
        }

        function showAddCamionModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter un camion manuellement</h3>
                    <form id="add-camion-form">
                        <div class="form-group">
                            <label for="add-user-id">Franchisé *</label>
                            <select id="add-user-id" name="user_id" required>
                                <option value="">Sélectionner un franchisé</option>
                                ${getFranchiseOptions().map(opt => 
                                    `<option value="${opt.value}">${opt.text}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-nom-camion">Nom du camion *</label>
                            <input type="text" id="add-nom-camion" name="nom_camion" required placeholder="Ex: Le Gourmand, Food Express">
                        </div>
                        <div class="form-group">
                            <label for="add-etat">État</label>
                            <select id="add-etat" name="etat">
                                ${getEtatOptions().map(opt => 
                                    `<option value="${opt.value}" ${opt.value === 'en_preparation' ? 'selected' : ''}>${opt.text}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add-date-livraison">Date de livraison</label>
                            <input type="date" id="add-date-livraison" name="date_livraison" min="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="form-group">
                            <label for="add-emplacement">Emplacement</label>
                            <input type="text" id="add-emplacement" name="emplacement" placeholder="Ex: Place de la République, Paris">
                        </div>
                        <div class="form-group">
                            <label for="add-menu">Menu</label>
                            <textarea id="add-menu" name="menu" rows="3" placeholder="Description du menu proposé..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="add-jours">Jours d'ouverture</label>
                            <input type="text" id="add-jours" name="jours" placeholder="Ex: Lundi au Vendredi, Week-ends uniquement">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeAddCamionModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('add-camion-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                console.log('🚛 AJOUT CAMION - Données à envoyer:', data);

                if (!data.user_id || !data.nom_camion) {
                    alert('❌ Veuillez remplir tous les champs obligatoires');
                    return;
                }

                try {
                    const response = await fetch('../api/camions/add.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📋 Résultat API ajout camion:', result);

                    if (result.success) {
                        const message = `✅ ${result.message}\nImmatriculation: ${result.immatriculation || 'Générée'}`;
                        alert(message);
                        closeAddCamionModal();
                        await loadCamionsTab();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }

                } catch (error) {
                    console.error('❌ Erreur ajout camion:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            });
        }

        function closeAddCamionModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function editCamion(camionId) {
            const camion = camionManager.data.camions.find(c => c.id == camionId);
            if (!camion) {
                alert('Camion non trouvé');
                return;
            }

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Modifier le camion #${camion.id}</h3>
                    <form id="edit-camion-form">
                        <div class="form-group">
                            <label for="edit-nom-camion">Nom du camion *</label>
                            <input type="text" id="edit-nom-camion" name="nom_camion" value="${camion.nom_camion || ''}" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-etat">État</label>
                            <select id="edit-etat" name="etat">
                                ${getEtatOptions().map(opt => 
                                    `<option value="${opt.value}" ${opt.value === camion.etat ? 'selected' : ''}>${opt.text}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-date-livraison">Date de livraison</label>
                            <input type="date" id="edit-date-livraison" name="date_livraison" value="${camion.date_livraison || ''}">
                        </div>
                        <div class="form-group">
                            <label for="edit-emplacement">Emplacement</label>
                            <input type="text" id="edit-emplacement" name="emplacement" value="${camion.emplacement || ''}">
                        </div>
                        <div class="form-group">
                            <label for="edit-menu">Menu</label>
                            <textarea id="edit-menu" name="menu" rows="3">${camion.menu || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit-jours">Jours d'ouverture</label>
                            <input type="text" id="edit-jours" name="jours" value="${camion.jours || ''}">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeEditCamionModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            // GESTION FORMULAIRE
            document.getElementById('edit-camion-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                data.id = camionId; // Ajouter l'ID

                console.log('🔄 MODIFICATION CAMION - Données à envoyer:', data);

                if (!data.nom_camion) {
                    alert('❌ Le nom du camion est obligatoire');
                    return;
                }

                try {
                    const response = await fetch('../api/camions/update.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('📋 Résultat API modification camion:', result);

                    if (result.success) {
                        alert('✅ Camion modifié avec succès !');
                        closeEditCamionModal();
                        await loadCamionsTab();
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }

                } catch (error) {
                    console.error('❌ Erreur modification camion:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            });
        }

        function closeEditCamionModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function validerDemande(demandeId) {
            const demande = camionManager.data.demandes.find(d => d.id == demandeId);
            if (!demande) {
                alert('Demande non trouvée');
                return;
            }

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Valider la demande de camion</h3>
                    
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                        <strong>Franchisé :</strong> ${demande.franchise_nom} ${demande.franchise_prenom}<br>
                        <strong>Email :</strong> ${demande.franchise_email}<br>
                        <strong>Camion demandé :</strong> ${demande.nom_camion}<br>
                        <strong>Emplacement :</strong> ${demande.emplacement}<br>
                        <strong>Menu :</strong> ${AdminCommon.utils.truncateText(demande.menu, 100)}<br>
                        <strong>Jours :</strong> ${demande.jours}
                    </div>
                    
                    <form id="validate-demande-form">
                        <div class="form-group">
                            <label for="validate-date-livraison">Date de livraison prévue *</label>
                            <input type="date" id="validate-date-livraison" name="date_livraison" required 
                                   min="${new Date().toISOString().split('T')[0]}"
                                   value="${new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0]}">
                            <small style="color:#666;">Par défaut : dans 2 semaines</small>
                        </div>
                        <div class="form-group">
                            <label for="validate-commentaire">Commentaire (optionnel)</label>
                            <textarea id="validate-commentaire" name="commentaire" rows="3" 
                                      placeholder="Instructions pour le franchisé, notes particulières..."></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeValidateDemandeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Valider la demande</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            // GESTION FORMULAIRE
            document.getElementById('validate-demande-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                console.log('✅ VALIDATION DEMANDE - Données à envoyer:', data);

                if (!data.date_livraison) {
                    alert('❌ La date de livraison est obligatoire');
                    return;
                }

                try {
                    const response = await fetch('../api/camions/validate_demande.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            demande_id: demandeId,
                            action: 'valider',
                            ...data
                        })
                    });

                    const result = await response.json();
                    console.log('📋 Résultat API validation:', result);

                    if (result.success) {
                        alert('✅ ' + result.message);
                        closeValidateDemandeModal();
                        await Promise.all([
                            loadDemandesTab(),
                            loadCamionsTab()
                        ]);
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }

                } catch (error) {
                    console.error('❌ Erreur validation demande:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            });
        }

        function closeValidateDemandeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function refuserDemande(demandeId) {
            const demande = camionManager.data.demandes.find(d => d.id == demandeId);
            if (!demande) {
                alert('Demande non trouvée');
                return;
            }

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Refuser la demande de camion</h3>
                    
                    <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                        <strong>⚠️ Attention :</strong> Cette action va refuser définitivement la demande de camion de <strong>${demande.franchise_nom} ${demande.franchise_prenom}</strong>.
                    </div>
                    
                    <form id="refuse-demande-form">
                        <div class="form-group">
                            <label for="refuse-commentaire">Raison du refus *</label>
                            <textarea id="refuse-commentaire" name="commentaire" rows="4" required
                                      placeholder="Expliquez pourquoi cette demande est refusée (ex: documentation incomplète, critères non respectés, etc.)"></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeRefuseDemandeModal()">Annuler</button>
                            <button type="submit" class="btn-action danger">Refuser définitivement</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('refuse-demande-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                console.log('❌ REFUS DEMANDE - Données à envoyer:', data);

                if (!data.commentaire || data.commentaire.trim().length < 10) {
                    alert('❌ Veuillez expliquer la raison du refus (minimum 10 caractères)');
                    return;
                }

                try {
                    const response = await fetch('../api/camions/validate_demande.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            demande_id: demandeId,
                            action: 'refuser',
                            ...data
                        })
                    });

                    const result = await response.json();
                    console.log('📋 Résultat API refus:', result);

                    if (result.success) {
                        alert('✅ ' + result.message);
                        closeRefuseDemandeModal();
                        await Promise.all([
                            loadDemandesTab(),
                            loadCamionsTab()
                        ]);
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }

                } catch (error) {
                    console.error('❌ Erreur refus demande:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            });
        }

        function closeRefuseDemandeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function getEtatOptions() {
            return Object.entries(camionManager.config.etatsLabels).map(([value, text]) => ({
                value,
                text
            }));
        }

        function getFranchiseOptions() {
            return [
                { value: '', text: 'Sélectionner un franchisé' },
                ...camionManager.data.franchises
                    .filter(f => f.role === 'franchise' && f.statut === 'valide')
                    .map(f => ({
                        value: f.id,
                        text: `${f.nom || ''} ${f.prenom || ''} (${f.email || ''})`
                    }))
            ];
        }

        function updateBadges() {
            // Mettre à jour le badge des demandes en attente
            const badgeElement = document.getElementById('badge-demandes');
            const count = camionManager.data.demandes?.length || 0;
            
            if (count > 0) {
                badgeElement.textContent = count;
                badgeElement.style.display = 'inline';
            } else {
                badgeElement.style.display = 'none';
            }
        }

        function planifierMaintenance(camionId) {
            AdminCommon.utils.showAlert(`Planification de maintenance pour le camion #${camionId} - Fonctionnalité à développer`, 'info');
        }

        function synchroniserEmplacement(camionId) {
            const camion = camionManager.data.camions.find(c => c.id == camionId);
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
                            <button type="button" class="btn-secondary" onclick="closeSyncEmplacementModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Synchroniser</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('sync-emplacement-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                if (!data.emplacement) {
                    alert('❌ Veuillez saisir un emplacement');
                    return;
                }

                try {
                    const response = await fetch('../api/camions/sync_emplacement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            camion_id: camionId,
                            emplacement: data.emplacement
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('✅ Emplacement synchronisé avec succès !');
                        closeSyncEmplacementModal();
                        await Promise.all([
                            loadCamionsTab(),
                            loadFranchises()
                        ]);
                    } else {
                        alert('❌ Erreur: ' + result.message);
                    }

                } catch (error) {
                    console.error('❌ Erreur synchronisation:', error);
                    alert('❌ Erreur réseau: ' + error.message);
                }
            });
        }

        function closeSyncEmplacementModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

    </script>
</body>
</html>