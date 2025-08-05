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

            <div id="tab-franchise" class="tab-content">
                <button class="add-btn" id="add-franchise-btn">+ Ajouter un franchisé</button>
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
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../js/admin/franchisés.js"></script>
    <script>
        function switchTab(tab) {
            document.getElementById('tab-franchise').style.display = tab === 'franchise' ? '' : 'none';
            document.getElementById('tab-validation').style.display = tab === 'validation' ? '' : 'none';
            document.getElementById('tab-franchise-btn').classList.toggle('active', tab === 'franchise');
            document.getElementById('tab-validation-btn').classList.toggle('active', tab === 'validation');
            
            if (tab === 'franchise') {
                loadFranchises();
            } else {
                loadValidation();
            }
        }

        async function loadFranchises() {
            try {
                const res = await fetch('../api/users/get_all.php');
                const franchises = await res.json();
                
                const tbody = document.querySelector('#franchise-table tbody');
                tbody.innerHTML = '';
                
                franchises.forEach(franchise => {
                    const statusClass = `status-${franchise.statut || 'valide'}`;
                    const statusText = {
                        'valide': 'Validé',
                        'en_attente': 'En attente',
                        'refuse': 'Refusé'
                    }[franchise.statut || 'valide'];
                    
                    tbody.innerHTML += `
                        <tr>
                            <td>${franchise.nom} ${franchise.prenom || ''}</td>
                            <td>${franchise.email}</td>
                            <td>${franchise.telephone || 'Non renseigné'}</td>
                            <td>${franchise.lieu_installation || 'Non renseigné'}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>${new Date(franchise.date_inscription).toLocaleDateString('fr-FR')}</td>
                            <td>
                                <button class="btn-action" onclick="editFranchise(${franchise.id})">Modifier</button>
                                <button class="btn-action danger" onclick="deleteFranchise(${franchise.id})">Supprimer</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Erreur lors du chargement des franchisés:', error);
            }
        }

        async function loadValidation() {
            try {
                const res = await fetch('../api/users/validation.php');
                const comptes = await res.json();
                
                document.getElementById('badge-validation').textContent = comptes.length;
                document.getElementById('badge-validation').style.display = comptes.length ? '' : 'none';
                
                const tbody = document.getElementById('liste-validation');
                tbody.innerHTML = '';
                
                comptes.forEach(compte => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${compte.nom} ${compte.prenom}</td>
                            <td>${compte.email}</td>
                            <td>${compte.telephone}</td>
                            <td>${compte.lieu_installation}</td>
                            <td title="${compte.motivation}">${compte.motivation.substring(0, 50)}${compte.motivation.length > 50 ? '...' : ''}</td>
                            <td>${new Date(compte.date_inscription).toLocaleDateString('fr-FR')}</td>
                            <td>
                                <button class="btn-action" onclick="validerCompte(${compte.id}, 'valider')">Valider</button>
                                <button class="btn-action danger" onclick="validerCompte(${compte.id}, 'refuser')">Refuser</button>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Erreur lors du chargement des validations:', error);
            }
        }

        async function validerCompte(userId, action) {
            try {
                const res = await fetch('../api/users/validation.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, action: action})
                });
                
                if (res.ok) {
                    loadValidation();
                    if (action === 'valider') {
                        loadFranchises(); 
                    }
                    alert(`Compte ${action === 'valider' ? 'validé' : 'refusé'} avec succès!`);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la validation du compte');
            }
        }

        function showAddFranchiseModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Ajouter un nouveau franchisé</h3>
                    <form id="add-franchise-form">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone *</label>
                            <input type="tel" id="telephone" name="telephone" required>
                        </div>
                        <div class="form-group">
                            <label for="lieu">Lieu d'installation souhaité *</label>
                            <input type="text" id="lieu" name="lieu" required>
                        </div>
                        <div class="form-group">
                            <label for="motivation">Motivation *</label>
                            <textarea id="motivation" name="motivation" required placeholder="Décrivez votre motivation pour devenir franchisé..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="statut">Statut initial</label>
                            <select id="statut" name="statut">
                                <option value="en_attente">En attente de validation</option>
                                <option value="valide">Validé directement</option>
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
            
            document.getElementById('add-franchise-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.role = 'franchise';
                data.password = 'temp123';
                
                try {
                    const res = await fetch('../api/users/register.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    
                    if (result.success) {
                        alert('Franchisé ajouté avec succès !');
                        closeModal();
                        loadFranchises();
                        loadValidation();
                    } else {
                        alert('Erreur: ' + result.message);
                    }
                } catch (err) {
                    alert('Erreur réseau');
                }
            };
        }

        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function editFranchise(id) {
            showEditFranchiseModal(id);
        }

        async function showEditFranchiseModal(id) {
            try {
                // Récupérer les données du franchisé
                const res = await fetch(`../api/users/get_one.php?id=${id}`);
                const user = await res.json();
                
                if (!user || user.success === false) {
                    alert('Erreur lors de la récupération des données du franchisé');
                    return;
                }
                
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <h3>Modifier le franchisé</h3>
                        <form id="edit-franchise-form">
                            <input type="hidden" name="id" value="${user.id}">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-nom">Nom *</label>
                                    <input type="text" id="edit-nom" name="nom" value="${user.nom || ''}" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-prenom">Prénom *</label>
                                    <input type="text" id="edit-prenom" name="prenom" value="${user.prenom || ''}" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-email">Email *</label>
                                <input type="email" id="edit-email" name="email" value="${user.email || ''}" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-telephone">Téléphone</label>
                                    <input type="tel" id="edit-telephone" name="telephone" value="${user.telephone || ''}" 
                                           placeholder="01 23 45 67 89">
                                </div>
                                <div class="form-group">
                                    <label for="edit-statut">Statut</label>
                                    <select id="edit-statut" name="statut">
                                        <option value="en_attente" ${user.statut === 'en_attente' ? 'selected' : ''}>En attente</option>
                                        <option value="valide" ${user.statut === 'valide' ? 'selected' : ''}>Validé</option>
                                        <option value="refuse" ${user.statut === 'refuse' ? 'selected' : ''}>Refusé</option>
                                        <option value="desactive" ${user.statut === 'desactive' ? 'selected' : ''}>Désactivé</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-lieu">Lieu d'installation</label>
                                <input type="text" id="edit-lieu" name="lieu_installation" 
                                       value="${user.lieu_installation || ''}" 
                                       placeholder="Ville, région...">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-motivation">Motivation</label>
                                <textarea id="edit-motivation" name="motivation" rows="3" 
                                          placeholder="Motivation pour devenir franchisé...">${user.motivation || ''}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-permis">Numéro de permis</label>
                                <input type="text" id="edit-permis" name="numero_permis" 
                                       value="${user.numero_permis || ''}" 
                                       placeholder="Optionnel - Format: AB123456789">
                                <small style="color: #666;">Obligatoire pour faire une demande de camion</small>
                            </div>
                            
                            <!-- Informations système (lecture seule) -->
                            <div class="form-row" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                                <div class="form-group">
                                    <label>Date d'inscription</label>
                                    <input type="text" value="${new Date(user.date_inscription).toLocaleDateString('fr-FR')}" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Dernière connexion</label>
                                    <input type="text" value="${user.derniere_connexion ? new Date(user.derniere_connexion).toLocaleDateString('fr-FR') : 'Jamais'}" readonly>
                                </div>
                            </div>
                            
                            <div class="modal-actions">
                                <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                                <button type="submit" class="btn-primary">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Gérer la soumission du formulaire
                document.getElementById('edit-franchise-form').onsubmit = async function(e) {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const data = Object.fromEntries(formData);
                    
                    try {
                        const res = await fetch('../api/users/update.php', {
                            method: 'PUT',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(data)
                        });
                        const result = await res.json();
                        
                        if (result.success) {
                            alert('Franchisé mis à jour avec succès !');
                            closeModal();
                            loadFranchises();
                            loadValidation();
                        } else {
                            alert('Erreur: ' + result.message);
                        }
                    } catch (err) {
                        console.error('Erreur:', err);
                        alert('Erreur réseau lors de la mise à jour');
                    }
                };
                
            } catch (error) {
                console.error('Erreur lors de la récupération des données:', error);
                alert('Erreur lors de la récupération des données du franchisé');
            }
        }

        async function deleteFranchise(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce franchisé ?\n\nCette action peut le désactiver au lieu de le supprimer s\'il a des camions ou commandes associés.')) {
                try {
                    const res = await fetch('../api/users/delete.php', {
                        method: 'DELETE',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    });
                    
                    const result = await res.json();
                    
                    if (result.success) {
                        alert(result.message);
                        loadFranchises();
                        loadValidation();
                    } else {
                        alert('Erreur: ' + result.message);
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur réseau lors de la suppression');
                }
            }
        }

        document.getElementById('add-franchise-btn').onclick = showAddFranchiseModal;

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            loadFranchises();
            loadValidation();
        });
    </script>
</body>
</html>