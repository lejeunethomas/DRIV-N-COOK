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
                <button class="tab-btn active" id="tab-camions-btn" onclick="switchTab('camions')">
                    Parc de camions
                </button>
                <button class="tab-btn" id="tab-demandes-btn" onclick="switchTab('demandes')">
                    Demandes à traiter <span class="badge" id="badge-demandes" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-camions" class="tab-content" style="display:block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0;">Parc de camions</h2>
                    <button class="add-btn" onclick="showAddCamionModal()">+ Ajouter un camion</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Camion #</th>
                            <th>Franchisé</th>
                            <th>État</th>
                            <th>Emplacement</th>
                            <th>Prochaine maintenance</th>
                            <th>Demande de maintenance</th>
                            <th>Date de livraison</th>
                        </tr>
                    </thead>
                    <tbody id="liste-camions">
                        <!-- Les données seront chargées ici -->
                    </tbody>
                </table>
            </div>

            <div id="tab-demandes" class="tab-content" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Franchisé</th>
                            <th>Nom du camion</th>
                            <th>N° Permis</th>
                            <th>Emplacement</th>
                            <th>Menu</th>
                            <th>Jours d'ouverture</th>
                            <th>Date de demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="liste-demandes-camion">
                        <!-- Les données seront chargées ici -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Variables globales
        let demandes = [];

        // Fonction pour changer d'onglet
        function switchTab(tab) {
            // Masquer tous les contenus
            document.getElementById('tab-camions').style.display = 'none';
            document.getElementById('tab-demandes').style.display = 'none';
            
            // Retirer la classe active
            document.getElementById('tab-camions-btn').classList.remove('active');
            document.getElementById('tab-demandes-btn').classList.remove('active');
            
            // Afficher le contenu sélectionné
            if (tab === 'camions') {
                document.getElementById('tab-camions').style.display = 'block';
                document.getElementById('tab-camions-btn').classList.add('active');
                loadCamions();
            } else {
                document.getElementById('tab-demandes').style.display = 'block';
                document.getElementById('tab-demandes-btn').classList.add('active');
                loadDemandes();
            }
        }

        // Charger les camions
        async function loadCamions() {
            try {
                const response = await fetch('../api/camions/list.php');
                if (!response.ok) throw new Error('Erreur réseau');
                
                const camions = await response.json();
                console.log('Camions chargés:', camions);
                
                const tbody = document.getElementById('liste-camions');
                if (!tbody) {
                    console.error('Tbody camions non trouvé');
                    return;
                }
                
                tbody.innerHTML = '';
                
                if (!Array.isArray(camions) || camions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Aucun camion trouvé</td></tr>';
                    return;
                }

                // Trier les camions
                camions.sort((a, b) => {
                    if ((b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0) !== 0)
                        return (b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0);
                    return (a.franchise_nom || '').localeCompare(b.franchise_nom || '');
                });

                camions.forEach(camion => {
                    const urgence = camion.demande_maintenance ? 'camion-urgence' : '';
                    const row = document.createElement('tr');
                    row.className = `accordion-row ${urgence}`;
                    row.onclick = () => toggleDetails(row);
                    
                    row.innerHTML = `
                        <td>${camion.id || '-'}</td>
                        <td>${camion.franchise_nom || 'Non assigné'}</td>
                        <td>${camion.etat || 'Actif'}</td>
                        <td>${camion.emplacement || 'Non renseigné'}</td>
                        <td>${camion.prochaine_maintenance || '-'}</td>
                        <td>
                            ${camion.demande_maintenance 
                                ? '<span class="camion-maintenance">À traiter</span>' 
                                : '<span class="camion-ok">OK</span>'}
                        </td>
                        <td>
                            ${camion.date_livraison 
                                ? '<span class="date-livraison">' + camion.date_livraison + '</span>' 
                                : '-'}
                        </td>
                    `;
                    
                    tbody.appendChild(row);
                    
                    // Ajouter la ligne de détails
                    const detailRow = document.createElement('tr');
                    detailRow.className = 'accordion-details';
                    detailRow.innerHTML = `
                        <td colspan="7">
                            <strong>Détails du camion #${camion.id || 'N/A'}</strong><br>
                            Menu : ${camion.menu || '-'}<br>
                            Jours d'ouverture : ${camion.jours || '-'}<br>
                            Historique entretiens : ${camion.historique_entretiens || '-'}<br>
                            <button class="btn-action" onclick="editCamion(${camion.id})">Modifier</button>
                            <button class="btn-action warning" onclick="planifierMaintenance(${camion.id})">Maintenance</button>
                        </td>
                    `;
                    tbody.appendChild(detailRow);
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des camions:', error);
                document.getElementById('liste-camions').innerHTML = 
                    '<tr><td colspan="7" style="text-align:center;color:red;">Erreur lors du chargement</td></tr>';
            }
        }

        // Charger les demandes
        async function loadDemandes() {
            try {
                const response = await fetch('../api/camions/demandes.php');
                if (!response.ok) throw new Error('Erreur réseau');
                
                demandes = await response.json();
                console.log('Demandes chargées:', demandes);
                
                document.getElementById('badge-demandes').textContent = demandes.length;
                document.getElementById('badge-demandes').style.display = demandes.length ? 'inline' : 'none';
                
                const tbody = document.getElementById('liste-demandes-camion');
                if (!tbody) {
                    console.error('Tbody demandes non trouvé');
                    return;
                }
                
                tbody.innerHTML = '';
                
                if (!Array.isArray(demandes) || demandes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">Aucune demande en attente</td></tr>';
                    return;
                }
                
                demandes.forEach(demande => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${demande.franchise_nom || ''} ${demande.franchise_prenom || ''}</td>
                        <td><strong>${demande.nom_camion || ''}</strong></td>
                        <td>${demande.numero_permis || 'Non renseigné'}</td>
                        <td>${demande.emplacement || 'Non renseigné'}</td>
                        <td>${demande.menu || 'Non renseigné'}</td>
                        <td>${demande.jours || 'Non renseigné'}</td>
                        <td>${demande.date_demande ? new Date(demande.date_demande).toLocaleDateString('fr-FR') : '-'}</td>
                        <td>
                            <button class="btn-action success" onclick="validerDemande(${demande.id})">Valider</button>
                            <button class="btn-action danger" onclick="refuserDemande(${demande.id})">Refuser</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des demandes:', error);
                document.getElementById('liste-demandes-camion').innerHTML = 
                    '<tr><td colspan="8" style="text-align:center;color:red;">Erreur lors du chargement</td></tr>';
            }
        }

        // Basculer les détails d'un camion
        function toggleDetails(row) {
            const next = row.nextElementSibling;
            if (next && next.classList.contains('accordion-details')) {
                next.classList.toggle('open');
            }
        }

        // FONCTIONS DE VALIDATION DES DEMANDES
        async function validerDemande(demandeId) {
            const demande = demandes.find(d => d.id == demandeId);
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
                        <strong>Menu :</strong> ${demande.menu}<br>
                        <strong>Jours :</strong> ${demande.jours}
                    </div>
                    
                    <form id="validation-form">
                        <div class="form-group">
                            <label for="date_livraison">Date de livraison prévue *</label>
                            <input type="date" id="date_livraison" name="date_livraison" required 
                                   min="${new Date().toISOString().split('T')[0]}"
                                   value="${new Date(Date.now() + 14*24*60*60*1000).toISOString().split('T')[0]}">
                            <small style="color: #666;">Par défaut : dans 2 semaines</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="commentaire">Commentaire (optionnel)</label>
                            <textarea id="commentaire" name="commentaire" rows="3" 
                                      placeholder="Instructions pour le franchisé, notes particulières..."></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-success">✅ Valider la demande</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('validation-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await submitValidation(demandeId, 'valider');
            });
        }

        async function refuserDemande(demandeId) {
            const demande = demandes.find(d => d.id == demandeId);
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
                    
                    <form id="refus-form">
                        <div class="form-group">
                            <label for="raison_refus">Raison du refus *</label>
                            <textarea id="raison_refus" name="commentaire" required rows="4" 
                                      placeholder="Expliquez pourquoi cette demande est refusée (ex: documentation incomplète, critères non respectés, etc.)"></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-danger">❌ Refuser la demande</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('refus-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await submitValidation(demandeId, 'refuser');
            });
        }

        // SOUMISSION DE LA VALIDATION
        async function submitValidation(demandeId, action) {
            try {
                const formData = action === 'valider' ? {
                    demande_id: demandeId,
                    action: action,
                    date_livraison: document.getElementById('date_livraison')?.value,
                    commentaire: document.getElementById('commentaire')?.value
                } : {
                    demande_id: demandeId,
                    action: action,
                    commentaire: document.getElementById('raison_refus')?.value
                };
                
                const response = await fetch('../api/camions/validate_demande.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ ${result.message}`);
                    closeModal();
                    loadDemandes(); // Recharger les demandes
                    loadCamions();  // Recharger le parc de camions
                } else {
                    alert(`❌ Erreur: ${result.message}`);
                }
                
            } catch (error) {
                console.error('Erreur lors de la validation:', error);
                alert('Erreur réseau lors de la validation');
            }
        }

        // MODAL D'AJOUT MANUEL DE CAMION
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
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-nom-camion">Nom du camion *</label>
                            <input type="text" id="add-nom-camion" name="nom_camion" required 
                                   placeholder="Ex: Le Gourmand, Food Express">
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="add-etat">État</label>
                                <select id="add-etat" name="etat">
                                    <option value="en_preparation">En préparation</option>
                                    <option value="pret">Prêt</option>
                                    <option value="en_service">En service</option>
                                    <option value="maintenance">En maintenance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="add-date-livraison">Date de livraison</label>
                                <input type="date" id="add-date-livraison" name="date_livraison" 
                                       min="${new Date().toISOString().split('T')[0]}">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-emplacement">Emplacement</label>
                            <input type="text" id="add-emplacement" name="emplacement" 
                                   placeholder="Ex: Place de la République, Paris">
                        </div>
                        
                        <div class="form-group">
                            <label for="add-menu">Menu</label>
                            <textarea id="add-menu" name="menu" rows="3" 
                                      placeholder="Description du menu proposé..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-jours">Jours d'ouverture</label>
                            <input type="text" id="add-jours" name="jours" 
                                   placeholder="Ex: Lundi au Vendredi, Week-ends uniquement">
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Ajouter le camion</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Charger la liste des franchisés
            loadFranchisesForSelect();
            
            // Gérer la soumission
            document.getElementById('add-camion-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await addCamion();
            });
        }

        // CHARGER LES FRANCHISÉS POUR LE SELECT
        async function loadFranchisesForSelect() {
            try {
                const response = await fetch('../api/users/get_all.php');
                const franchises = await response.json();
                
                const select = document.getElementById('add-user-id');
                select.innerHTML = '<option value="">Sélectionner un franchisé</option>';
                
                franchises.forEach(franchise => {
                    if (franchise.statut === 'valide') { // Seulement les franchisés validés
                        const option = document.createElement('option');
                        option.value = franchise.id;
                        option.textContent = `${franchise.nom} ${franchise.prenom} (${franchise.email})`;
                        select.appendChild(option);
                    }
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des franchisés:', error);
            }
        }

        // AJOUTER UN CAMION
        async function addCamion() {
            const formData = new FormData(document.getElementById('add-camion-form'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/camions/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ ${result.message}\nImmatriculation: ${result.immatriculation}`);
                    closeModal();
                    loadCamions();
                } else {
                    alert(`❌ Erreur: ${result.message}`);
                }
                
            } catch (error) {
                console.error('Erreur lors de l\'ajout:', error);
                alert('Erreur réseau lors de l\'ajout');
            }
        }

        // MODIFIER UN CAMION
        async function editCamion(camionId) {
            // Récupérer les données du camion
            const response = await fetch('../api/camions/list.php');
            const camions = await response.json();
            const camion = camions.find(c => c.id == camionId);
            
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
                        <input type="hidden" name="id" value="${camion.id}">
                        
                        <div class="form-group">
                            <label for="edit-nom-camion">Nom du camion *</label>
                            <input type="text" id="edit-nom-camion" name="nom_camion" required 
                                   value="${camion.nom_camion || ''}">
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="edit-etat">État</label>
                                <select id="edit-etat" name="etat">
                                    <option value="en_preparation" ${camion.etat === 'en_preparation' ? 'selected' : ''}>En préparation</option>
                                    <option value="pret" ${camion.etat === 'pret' ? 'selected' : ''}>Prêt</option>
                                    <option value="en_service" ${camion.etat === 'en_service' ? 'selected' : ''}>En service</option>
                                    <option value="maintenance" ${camion.etat === 'maintenance' ? 'selected' : ''}>En maintenance</option>
                                    <option value="desactive" ${camion.etat === 'desactive' ? 'selected' : ''}>Désactivé</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-date-livraison">Date de livraison</label>
                                <input type="date" id="edit-date-livraison" name="date_livraison" 
                                       value="${camion.date_livraison || ''}">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-emplacement">Emplacement</label>
                            <input type="text" id="edit-emplacement" name="emplacement" 
                                   value="${camion.emplacement || ''}">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-menu">Menu</label>
                            <textarea id="edit-menu" name="menu" rows="3">${camion.menu || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-jours">Jours d'ouverture</label>
                            <input type="text" id="edit-jours" name="jours" 
                                   value="${camion.jours || ''}">
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('edit-camion-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await updateCamion();
            });
        }

        // METTRE À JOUR UN CAMION
        async function updateCamion() {
            const formData = new FormData(document.getElementById('edit-camion-form'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/camions/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Camion modifié avec succès !');
                    closeModal();
                    loadCamions();
                } else {
                    alert(`❌ Erreur: ${result.message}`);
                }
                
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                alert('Erreur réseau lors de la modification');
            }
        }

        // PLANIFIER UNE MAINTENANCE
        async function planifierMaintenance(camionId) {
            alert(`Planification de maintenance pour le camion #${camionId} - Fonctionnalité à développer`);
            // TODO: Implémenter la planification de maintenance
        }

        // MODAL DE CONFIRMATION
        function showConfirmationModal(message, onConfirm) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Confirmation</h3>
                    <p>${message}</p>
                    <div class="modal-actions">
                        <button class="btn-secondary" onclick="closeModal()">Annuler</button>
                        <button class="btn-primary" id="confirm-btn">Confirmer</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('confirm-btn').addEventListener('click', function() {
                closeModal();
                onConfirm();
            });
        }

        // FONCTIONS UTILITAIRES
        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => modal.remove());
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page camions chargée, initialisation...');
            
            // Vérifier les éléments
            const tabCamions = document.getElementById('tab-camions');
            const tabDemandes = document.getElementById('tab-demandes');
            const listeCamions = document.getElementById('liste-camions');
            const listeDemandes = document.getElementById('liste-demandes-camion');
            
            console.log('Éléments trouvés:', {
                tabCamions: !!tabCamions,
                tabDemandes: !!tabDemandes,
                listeCamions: !!listeCamions,
                listeDemandes: !!listeDemandes
            });
            
            // Charger les données initiales
            loadCamions();
            loadDemandes();
        });
    </script>
</body>
</html>