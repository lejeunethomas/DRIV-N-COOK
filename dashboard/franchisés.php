<?php
require_once '../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des franchisés - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .status-valide {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-en_attente {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-refuse {
            background: #ffebee;
            color: #d32f2f;
        }
    </style>
</head>
<body data-role="admin">
    <div class="dashboard-layout">
        <nav class="sidebar admin">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php" class="active">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php">Gérer les produits</a>
            <a href="entrepots.php">Gérer les entrepôts</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content admin">
            <h1 class="admin">Gestion des franchisés</h1>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="AdminCommon.utils.switchTab('franchise', loadFranchises)">
                    Tous les franchisés
                </button>
                <button class="tab-btn" onclick="AdminCommon.utils.switchTab('validation', loadValidation)">
                    Comptes à valider <span class="badge" id="badge-validation" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-franchise" class="tab-content active">
                <button class="add-btn" onclick="showAddFranchiseModal()">+ Ajouter un franchisé</button>
                <div id="franchise-table-container"></div>
            </div>

            <div id="tab-validation" class="tab-content">
                <h2>Comptes franchisés en attente de validation</h2>
                <div id="validation-table-container"></div>
            </div>
        </main>
    </div>

    <script src="../js/admin/common.js"></script>

    <script>
        const franchiseManager = {
            data: { franchises: [], validationQueue: [] },
            
            config: {
                endpoints: {
                    getAll: '../api/users/get_all.php',
                    validation: '../api/users/validation.php',
                    register: '../api/users/register.php',
                    update: '../api/users/update.php',
                    delete: '../api/users/delete.php'
                },
                
                tables: {
                    franchise: {
                        headers: ['Nom complet', 'Email', 'Téléphone', 'Lieu', 'Statut', 'Date inscription', 'Actions'],
                        rowBuilder: (franchise) => [
                            `${franchise.nom || ''} ${franchise.prenom || ''}`,
                            franchise.email || '',
                            franchise.telephone || 'Non renseigné',
                            franchise.lieu_installation || 'Non renseigné',
                            `<span class="status-badge status-${franchise.statut || 'valide'}">${getStatusLabel(franchise.statut)}</span>`,
                            AdminCommon.utils.formatDate(franchise.date_inscription),
                            `<button class="btn-action" onclick="editFranchise(${franchise.id})">Modifier</button>
                             <button class="btn-action danger" onclick="deleteFranchise(${franchise.id})">Supprimer</button>`
                        ]
                    },
                    
                    validation: {
                        headers: ['Nom complet', 'Email', 'Téléphone', 'Lieu souhaité', 'Motivation', 'Date inscription', 'Actions'],
                        rowBuilder: (compte) => [
                            `${compte.nom || ''} ${compte.prenom || ''}`,
                            compte.email || '',
                            compte.telephone || 'Non renseigné',
                            compte.lieu_installation || 'Non renseigné',
                            `<span title="${compte.motivation || ''}">${AdminCommon.utils.truncateText(compte.motivation)}</span>`,
                            AdminCommon.utils.formatDate(compte.date_inscription),
                            `<button class="btn-action success" onclick="validerCompte(${compte.id}, 'valider')">Valider</button>
                             <button class="btn-action danger" onclick="validerCompte(${compte.id}, 'refuser')">Refuser</button>`
                        ]
                    }
                },
                
                statusLabels: {
                    'valide': 'Validé',
                    'en_attente': 'En attente', 
                    'refuse': 'Refusé'
                },
                
                formFields: {
                    base: [
                        { name: 'nom', label: 'Nom', type: 'text', required: true },
                        { name: 'prenom', label: 'Prénom', type: 'text', required: true },
                        { name: 'email', label: 'Email', type: 'email', required: true },
                        { name: 'telephone', label: 'Téléphone', type: 'tel' },
                        { name: 'numero_permis', label: 'Numéro de permis', type: 'text' },
                        { name: 'lieu_installation', label: 'Lieu d\'installation', type: 'text', required: true }
                    ],
                    add: [
                        { name: 'password', label: 'Mot de passe temporaire', type: 'password', required: true },
                        { name: 'motivation', label: 'Motivation', type: 'textarea' }
                    ]
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadFranchises();
            loadValidation();
        });

        async function loadFranchises() {
            try {
                const franchises = await AdminCommon.utils.apiRequest(franchiseManager.config.endpoints.getAll);
                franchiseManager.data.franchises = franchises;
                
                AdminCommon.utils.createTable({
                    containerId: 'franchise-table-container',
                    headers: franchiseManager.config.tables.franchise.headers,
                    data: franchises,
                    rowBuilder: (franchise) => {
                        const row = document.createElement('tr');
                        const cells = franchiseManager.config.tables.franchise.rowBuilder(franchise);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucun franchisé trouvé'
                });
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des franchisés', 'error');
            }
        }

        async function loadValidation() {
            try {
                const comptes = await AdminCommon.utils.apiRequest(franchiseManager.config.endpoints.validation);
                franchiseManager.data.validationQueue = comptes;
                
                AdminCommon.utils.createTable({
                    containerId: 'validation-table-container',
                    headers: franchiseManager.config.tables.validation.headers,
                    data: comptes,
                    rowBuilder: (compte) => {
                        const row = document.createElement('tr');
                        const cells = franchiseManager.config.tables.validation.rowBuilder(compte);
                        row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
                        return row;
                    },
                    emptyMessage: 'Aucun compte en attente'
                });
                
                AdminCommon.utils.updateTabBadge('validation', comptes.length);
                
            } catch (error) {
                AdminCommon.utils.showAlert('Erreur lors du chargement des validations', 'error');
            }
        }

        function getStatusLabel(statut) {
            return franchiseManager.config.statusLabels[statut || 'valide'];
        }

        function showAddFranchiseModal() {
            const fields = [
                ...franchiseManager.config.formFields.base,
                ...franchiseManager.config.formFields.add,
                { 
                    name: 'statut', 
                    label: 'Statut', 
                    type: 'select', 
                    defaultValue: 'valide',
                    options: Object.entries(franchiseManager.config.statusLabels).map(([value, text]) => ({ value, text }))
                }
            ];

            AdminCommon.utils.createFormModal({
                title: 'Ajouter un nouveau franchisé',
                fields: fields,
                onSubmit: async (data, isEdit) => {
                    data.role = 'franchise';
                    data.admin_create = true;
                    
                    const success = await AdminCommon.utils.saveData(
                        franchiseManager.config.endpoints.register, 
                        data
                    );
                    
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadFranchises();
                        await loadValidation();
                    }
                }
            });
        }

        function editFranchise(id) {
            const franchise = franchiseManager.data.franchises.find(f => f.id == id);
            if (!franchise) {
                AdminCommon.utils.showAlert('Franchisé non trouvé', 'error');
                return;
            }

            const fields = [
                ...franchiseManager.config.formFields.base,
                { 
                    name: 'statut', 
                    label: 'Statut', 
                    type: 'select',
                    options: Object.entries(franchiseManager.config.statusLabels).map(([value, text]) => ({ value, text }))
                }
            ];

            AdminCommon.utils.createFormModal({
                title: `Modifier le franchisé #${id}`,
                data: franchise,
                fields: fields,
                onSubmit: async (data, isEdit) => {
                    const success = await AdminCommon.utils.saveData(
                        franchiseManager.config.endpoints.update, 
                        data, 
                        true
                    );
                    
                    if (success) {
                        AdminCommon.utils.closeModal();
                        await loadFranchises();
                    }
                }
            });
        }

        async function validerCompte(userId, action) {
            if (!confirm(`Êtes-vous sûr de vouloir ${action} ce compte ?`)) return;
            
            const success = await AdminCommon.utils.saveData(
                franchiseManager.config.endpoints.validation,
                { user_id: userId, action: action }
            );
            
            if (success) {
                await loadFranchises();
                await loadValidation();
            }
        }

        async function deleteFranchise(id) {
            const success = await AdminCommon.utils.deleteData(
                franchiseManager.config.endpoints.delete,
                { id: id },
                'Êtes-vous sûr de vouloir supprimer ce franchisé ?\n\nCette action désactivera le compte plutôt que de le supprimer définitivement.'
            );
            
            if (success) {
                await loadFranchises();
            }
        }

    </script>
</body>
</html>