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
<body>
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
                <button class="tab-btn active" id="tab-franchise-btn" onclick="switchTab('franchise')">
                    Tous les franchisés
                </button>
                <button class="tab-btn" id="tab-validation-btn" onclick="switchTab('validation')">
                    Comptes à valider <span class="badge" id="badge-validation" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-franchise" class="tab-content" style="display: block;">
                <button class="add-btn" onclick="showAddFranchiseModal()">+ Ajouter un franchisé</button>
                <table id="franchise-table">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th>Date inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <div id="tab-validation" class="tab-content" style="display:none;">
                <h2>Comptes franchisés en attente de validation</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Lieu souhaité</th>
                            <th>Motivation</th>
                            <th>Date inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="liste-validation">
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
            document.getElementById('tab-franchise').style.display = 'none';
            document.getElementById('tab-validation').style.display = 'none';
            
            // Retirer la classe active de tous les boutons
            document.getElementById('tab-franchise-btn').classList.remove('active');
            document.getElementById('tab-validation-btn').classList.remove('active');
            
            // Afficher le contenu sélectionné et activer le bouton
            if (tab === 'franchise') {
                document.getElementById('tab-franchise').style.display = 'block';
                document.getElementById('tab-franchise-btn').classList.add('active');
                loadFranchises();
            } else {
                document.getElementById('tab-validation').style.display = 'block';
                document.getElementById('tab-validation-btn').classList.add('active');
                loadValidation();
            }
        }

        // Charger tous les franchisés
        async function loadFranchises() {
            try {
                const res = await fetch('../api/users/get_all.php');
                if (!res.ok) throw new Error('Erreur réseau');
                
                const franchises = await res.json();
                console.log('Franchisés chargés:', franchises);
                
                const tbody = document.querySelector('#franchise-table tbody');
                if (!tbody) {
                    console.error('Tbody non trouvé');
                    return;
                }
                
                tbody.innerHTML = '';
                
                if (!Array.isArray(franchises) || franchises.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Aucun franchisé trouvé</td></tr>';
                    return;
                }
                
                franchises.forEach(franchise => {
                    const statusClass = `status-${franchise.statut || 'valide'}`;
                    const statusText = {
                        'valide': 'Validé',
                        'en_attente': 'En attente',
                        'refuse': 'Refusé'
                    }[franchise.statut || 'valide'];
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${franchise.nom || ''} ${franchise.prenom || ''}</td>
                        <td>${franchise.email || ''}</td>
                        <td>${franchise.telephone || 'Non renseigné'}</td>
                        <td>${franchise.lieu_installation || 'Non renseigné'}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${franchise.date_inscription ? new Date(franchise.date_inscription).toLocaleDateString('fr-FR') : '-'}</td>
                        <td>
                            <button class="btn-action" onclick="editFranchise(${franchise.id})">Modifier</button>
                            <button class="btn-action danger" onclick="deleteFranchise(${franchise.id})">Supprimer</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des franchisés:', error);
                document.querySelector('#franchise-table tbody').innerHTML = 
                    '<tr><td colspan="7" style="text-align:center;color:red;">Erreur lors du chargement</td></tr>';
            }
        }

        // Charger les comptes en attente de validation
        async function loadValidation() {
            try {
                const res = await fetch('../api/users/validation.php');
                if (!res.ok) throw new Error('Erreur réseau');
                
                const comptes = await res.json();
                console.log('Comptes en attente:', comptes);
                
                document.getElementById('badge-validation').textContent = comptes.length;
                document.getElementById('badge-validation').style.display = comptes.length ? 'inline' : 'none';
                
                const tbody = document.getElementById('liste-validation');
                if (!tbody) {
                    console.error('Tbody validation non trouvé');
                    return;
                }
                
                tbody.innerHTML = '';
                
                if (!Array.isArray(comptes) || comptes.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Aucun compte en attente</td></tr>';
                    return;
                }
                
                comptes.forEach(compte => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${compte.nom || ''} ${compte.prenom || ''}</td>
                        <td>${compte.email || ''}</td>
                        <td>${compte.telephone || 'Non renseigné'}</td>
                        <td>${compte.lieu_installation || 'Non renseigné'}</td>
                        <td title="${compte.motivation || ''}">${compte.motivation ? (compte.motivation.substring(0, 50) + (compte.motivation.length > 50 ? '...' : '')) : 'Non renseigné'}</td>
                        <td>${compte.date_inscription ? new Date(compte.date_inscription).toLocaleDateString('fr-FR') : '-'}</td>
                        <td>
                            <button class="btn-action success" onclick="validerCompte(${compte.id}, 'valider')">Valider</button>
                            <button class="btn-action danger" onclick="validerCompte(${compte.id}, 'refuser')">Refuser</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
            } catch (error) {
                console.error('Erreur lors du chargement des validations:', error);
                document.getElementById('liste-validation').innerHTML = 
                    '<tr><td colspan="7" style="text-align:center;color:red;">Erreur lors du chargement</td></tr>';
            }
        }

        // Valider ou refuser un compte
        async function validerCompte(userId, action) {
            try {
                const res = await fetch('../api/users/validation.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, action: action})
                });
                
                const result = await res.json();
                
                if (result.success) {
                    alert(`Compte ${action === 'valider' ? 'validé' : 'refusé'} avec succès!`);
                    loadValidation();
                    if (action === 'valider') {
                        loadFranchises(); 
                    }
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la validation du compte');
            }
        }

        // MODAL D'AJOUT DE FRANCHISÉ
        function showAddFranchiseModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter un nouveau franchisé</h3>
                    <form id="add-franchise-form">
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="add-nom">Nom *</label>
                                <input type="text" id="add-nom" name="nom" required>
                            </div>
                            <div class="form-group">
                                <label for="add-prenom">Prénom *</label>
                                <input type="text" id="add-prenom" name="prenom" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-email">Email *</label>
                            <input type="email" id="add-email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-password">Mot de passe temporaire *</label>
                            <input type="password" id="add-password" name="password" required>
                            <small style="color: #666;">Le franchisé pourra le modifier lors de sa première connexion</small>
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="add-telephone">Téléphone</label>
                                <input type="tel" id="add-telephone" name="telephone" placeholder="Ex: 06 12 34 56 78">
                            </div>
                            <div class="form-group">
                                <label for="add-numero-permis">Numéro de permis</label>
                                <input type="text" id="add-numero-permis" name="numero_permis" placeholder="Ex: 123456789012">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-lieu">Lieu d'installation souhaité *</label>
                            <input type="text" id="add-lieu" name="lieu_installation" required placeholder="Ex: Paris, Lyon, Marseille...">
                        </div>
                        
                        <div class="form-group">
                            <label for="add-motivation">Motivation *</label>
                            <textarea id="add-motivation" name="motivation" required rows="3" 
                                      placeholder="Motivations pour devenir franchisé..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-statut">Statut initial</label>
                            <select id="add-statut" name="statut">
                                <option value="valide">Validé (accès immédiat)</option>
                                <option value="en_attente">En attente de validation</option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Ajouter le franchisé</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('add-franchise-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await addFranchise();
            });
        }

        // FONCTION D'AJOUT 
        async function addFranchise() {
            const formData = new FormData(document.getElementById('add-franchise-form'));
            const data = Object.fromEntries(formData);
            
            const registrationData = {
                nom: data.nom,
                prenom: data.prenom,
                email: data.email,
                password: data.password,
                telephone: data.telephone || '',
                numero_permis: data.numero_permis || '',
                lieu_installation: data.lieu_installation,
                motivation: data.motivation,
                role: 'franchise', 
                statut: data.statut || 'valide', 
                admin_create: true 
            };
            
            try {
                console.log('Données à envoyer:', registrationData);
                
                const response = await fetch('../api/users/register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(registrationData)
                });
                
                const result = await response.json();
                console.log('Réponse de register.php:', result);
                
                if (result.success) {
                    alert('Franchisé ajouté avec succès !');
                    closeModal();
                    loadFranchises();
                    loadValidation();
                } else {
                    alert('Erreur : ' + result.message);
                }
                
            } catch (error) {
                console.error('Erreur lors de l\'ajout:', error);
                alert('Erreur réseau lors de l\'ajout');
            }
        }

        // MODAL DE MODIFICATION
        function editFranchise(id) {
            const franchise = Array.from(document.querySelectorAll('#franchise-table tbody tr')).find(row => {
                const editBtn = row.querySelector('.btn-action:not(.danger)');
                return editBtn && editBtn.onclick.toString().includes(id);
            });
            
            if (!franchise) {
                alert('Franchisé non trouvé');
                return;
            }
            
            // Récupérer les données de la ligne
            const cells = franchise.querySelectorAll('td');
            const nomComplet = cells[0].textContent.trim().split(' ');
            const nom = nomComplet.pop();
            const prenom = nomComplet.join(' ');
            const email = cells[1].textContent.trim();
            const telephone = cells[2].textContent.trim();
            const lieu = cells[3].textContent.trim();
            const statutBadge = cells[4].querySelector('.status-badge');
            const currentStatut = statutBadge.classList.contains('status-valide') ? 'valide' : 
                                 statutBadge.classList.contains('status-en_attente') ? 'en_attente' : 'refuse';
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Modifier le franchisé #${id}</h3>
                    <form id="edit-franchise-form">
                        <input type="hidden" name="id" value="${id}">
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="edit-nom">Nom *</label>
                                <input type="text" id="edit-nom" name="nom" value="${nom}" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-prenom">Prénom *</label>
                                <input type="text" id="edit-prenom" name="prenom" value="${prenom}" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-email">Email *</label>
                            <input type="email" id="edit-email" name="email" value="${email}" required readonly 
                                   title="L'email ne peut pas être modifié">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-telephone">Téléphone</label>
                            <input type="tel" id="edit-telephone" name="telephone" 
                                   value="${telephone !== 'Non renseigné' ? telephone : ''}" 
                                   placeholder="Ex: 06 12 34 56 78">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-lieu">Lieu d'installation *</label>
                            <input type="text" id="edit-lieu" name="lieu_installation" 
                                   value="${lieu !== 'Non renseigné' ? lieu : ''}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-statut">Statut</label>
                            <select id="edit-statut" name="statut">
                                <option value="valide" ${currentStatut === 'valide' ? 'selected' : ''}>Validé</option>
                                <option value="en_attente" ${currentStatut === 'en_attente' ? 'selected' : ''}>En attente</option>
                                <option value="refuse" ${currentStatut === 'refuse' ? 'selected' : ''}>Refusé</option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">Modifier</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Gérer la soumission
            document.getElementById('edit-franchise-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                await updateFranchise();
            });
        }

        // FONCTION DE MODIFICATION
        async function updateFranchise() {
            const formData = new FormData(document.getElementById('edit-franchise-form'));
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('../api/users/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Franchisé modifié avec succès !');
                    closeModal();
                    loadFranchises();
                    loadValidation(); // Actualiser les validations aussi
                } else {
                    alert('Erreur : ' + result.message);
                }
                
            } catch (error) {
                console.error('Erreur lors de la modification:', error);
                alert('Erreur réseau lors de la modification');
            }
        }

        // FONCTION DE SUPPRESSION
        async function deleteFranchise(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce franchisé ?\n\nCette action désactivera le compte plutôt que de le supprimer définitivement.')) {
                return;
            }
            
            try {
                const response = await fetch('../api/users/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Franchisé supprimé avec succès !');
                    loadFranchises();
                    loadValidation();
                } else {
                    alert('Erreur : ' + result.message);
                }
                
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                alert('Erreur réseau lors de la suppression');
            }
        }

        // FONCTION UTILITAIRE
        function closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => modal.remove());
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page chargée, initialisation...');
            
            // Vérifier que les éléments existent
            const tabFranchise = document.getElementById('tab-franchise');
            const tabValidation = document.getElementById('tab-validation');
            const tableBody = document.querySelector('#franchise-table tbody');
            
            console.log('Éléments trouvés:', {
                tabFranchise: !!tabFranchise,
                tabValidation: !!tabValidation,
                tableBody: !!tableBody
            });
            
            // Charger les données initiales
            loadFranchises();
            loadValidation();
        });
    </script>
</body>
</html>