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
                loadFranchises()
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
            const fields = camionManager.config.formFields.camionAdd.map(field => {
                if (field.options === 'getFranchiseOptions') {
                    field.options = getFranchiseOptions();
                } else if (field.options === 'getEtatOptions') {
                    field.options = getEtatOptions();
                }
                return field;
            });

            AdminCommon.utils.createFormModal({
                title: 'Ajouter un camion manuellement',
                fields: fields,
                onSubmit: async (data, isEdit) => {
                    const result = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.add, {
                        method: 'POST',
                        data,
                        showLoader: true
                    });
                    
                    if (result.success) {
                        const message = `${result.message}\nImmatriculation: ${result.immatriculation || 'Générée'}`;
                        AdminCommon.utils.showAlert(message, 'success');
                        AdminCommon.utils.closeModal();
                        await loadCamionsTab();
                    } else {
                        AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                    }
                }
            });
        }

        function editCamion(camionId) {
            const camion = camionManager.data.camions.find(c => c.id == camionId);
            if (!camion) {
                AdminCommon.utils.showAlert('Camion non trouvé', 'error');
                return;
            }
            
            const fields = camionManager.config.formFields.camionEdit.map(field => {
                if (field.options === 'getEtatOptions') {
                    field.options = getEtatOptions();
                }
                return field;
            });

            AdminCommon.utils.createFormModal({
                title: `Modifier le camion #${camion.id}`,
                data: camion,
                fields: fields,
                onSubmit: async (data, isEdit) => {
                    const result = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.update, {
                        method: 'PUT',
                        data,
                        showLoader: true
                    });
                    
                    if (result.success) {
                        AdminCommon.utils.showAlert('Camion modifié avec succès !', 'success');
                        AdminCommon.utils.closeModal();
                        await loadCamionsTab();
                    } else {
                        AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                    }
                }
            });
        }

        function validerDemande(demandeId) {
            const demande = camionManager.data.demandes.find(d => d.id == demandeId);
            if (!demande) {
                AdminCommon.utils.showAlert('Demande non trouvée', 'error');
                return;
            }
            
            const infoHTML = `
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <strong>Franchisé :</strong> ${demande.franchise_nom} ${demande.franchise_prenom}<br>
                    <strong>Email :</strong> ${demande.franchise_email}<br>
                    <strong>Camion demandé :</strong> ${demande.nom_camion}<br>
                    <strong>Emplacement :</strong> ${demande.emplacement}<br>
                    <strong>Menu :</strong> ${AdminCommon.utils.truncateText(demande.menu, 100)}<br>
                    <strong>Jours :</strong> ${demande.jours}
                </div>
            `;

            AdminCommon.utils.createFormModal({
                title: 'Valider la demande de camion',
                fields: [
                    { 
                        name: 'date_livraison', 
                        label: 'Date de livraison prévue', 
                        type: 'date', 
                        required: true,
                        attributes: `min="${new Date().toISOString().split('T')[0]}"`,
                        defaultValue: new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0],
                        help: 'Par défaut : dans 2 semaines'
                    },
                    { 
                        name: 'commentaire', 
                        label: 'Commentaire (optionnel)', 
                        type: 'textarea', 
                        attributes: 'rows="3" placeholder="Instructions pour le franchisé, notes particulières..."'
                    }
                ],
                onSubmit: async (data, isEdit) => {
                    await submitValidation(demandeId, 'valider', data);
                },
                content: infoHTML
            });
        }

        function refuserDemande(demandeId) {
            const demande = camionManager.data.demandes.find(d => d.id == demandeId);
            if (!demande) {
                AdminCommon.utils.showAlert('Demande non trouvée', 'error');
                return;
            }
            
            const warningHTML = `
                <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                    <strong>⚠️ Attention :</strong> Cette action va refuser définitivement la demande de camion de <strong>${demande.franchise_nom} ${demande.franchise_prenom}</strong>.
                </div>
            `;

            AdminCommon.utils.createFormModal({
                title: 'Refuser la demande de camion',
                fields: [
                    { 
                        name: 'commentaire', 
                        label: 'Raison du refus', 
                        type: 'textarea', 
                        required: true,
                        attributes: 'rows="4" placeholder="Expliquez pourquoi cette demande est refusée (ex: documentation incomplète, critères non respectés, etc.)"'
                    }
                ],
                onSubmit: async (data, isEdit) => {
                    await submitValidation(demandeId, 'refuser', data);
                },
                content: warningHTML
            });
        }

        async function submitValidation(demandeId, action, data) {
            try {
                const formData = {
                    demande_id: demandeId,
                    action: action,
                    ...data
                };
                
                const result = await AdminCommon.utils.apiRequest(camionManager.config.endpoints.validate, {
                    method: 'POST',
                    data: formData,
                    showLoader: true
                });
                
                if (result.success) {
                    AdminCommon.utils.showAlert(result.message, 'success');
                    AdminCommon.utils.closeModal();
                    await Promise.all([
                        loadDemandesTab(),
                        loadCamionsTab()
                    ]);
                } else {
                    AdminCommon.utils.showAlert('Erreur: ' + result.message, 'error');
                }
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur réseau lors de la validation', 'error');
            }
        }

        function updateBadges() {
            if (camionManager.data.demandes) {
                AdminCommon.utils.updateTabBadge('demandes', camionManager.data.demandes.length);
            }
        }

        function getFranchiseOptions() {
            const options = [{ value: '', text: 'Sélectionner un franchisé' }];
            camionManager.data.franchises.forEach(franchise => {
                if (franchise.statut === 'valide') {
                    options.push({
                        value: franchise.id,
                        text: `${franchise.nom} ${franchise.prenom} (${franchise.email})`
                    });
                }
            });
            return options;
        }

        function getEtatOptions() {
            return Object.entries(camionManager.config.etatsLabels).map(([value, text]) => ({
                value, text
            }));
        }

        function planifierMaintenance(camionId) {
            AdminCommon.utils.showAlert(`Planification de maintenance pour le camion #${camionId} - Fonctionnalité à développer`, 'info');
        }

    </script>
</body>
</html>