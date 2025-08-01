<?php
require_once '../includes/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des franchisés - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .tabs { display: flex; gap: 2rem; margin-bottom: 2rem; }
        .tab-btn {
            background: #1976d2; color: #fff; border: none; border-radius: 8px 8px 0 0;
            padding: 1rem 2rem; font-weight: bold; cursor: pointer; font-size: 1.1rem;
            position: relative;
        }
        .tab-btn.active { background: #1565c0; }
        .badge {
            background: #e64a19; color: #fff; border-radius: 12px; padding: 0.2em 0.7em;
            font-size: 0.9em; position: absolute; top: 0.5em; right: -1.2em;
        }
        .btn-action { 
            background: #1976d2; color: #fff; border: none; border-radius: 6px; 
            padding: 0.5rem 1.2rem; margin: 0 0.3rem; cursor: pointer; 
        }
        .btn-action:hover { background: #1565c0; }
        .btn-action.danger { background: #f44336; }
        .btn-action.danger:hover { background: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th, td { padding: 0.7rem 1rem; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f3f8fd; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        .status-badge {
            padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold;
        }
        .status-valide { background: #e8f5e8; color: #2e7d32; }
        .status-en_attente { background: #fff3e0; color: #f57c00; }
        .status-refuse { background: #ffebee; color: #d32f2f; }
        
        /* Modal styles */
        .modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.6); display: flex; justify-content: center; 
            align-items: center; z-index: 1000;
        }
        .modal-content {
            background: white; padding: 2.5rem; border-radius: 12px; 
            max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .modal h3 { margin-top: 0; color: #1976d2; }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 0.8rem; border: 2px solid #ddd; border-radius: 6px;
            font-size: 1rem; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #1976d2; outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .modal-actions {
            display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;
        }
        .btn-primary {
            background: #1976d2; color: white; border: none; padding: 0.8rem 1.5rem;
            border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .btn-primary:hover { background: #1565c0; }
        .btn-secondary {
            background: #666; color: white; border: none; padding: 0.8rem 1.5rem;
            border-radius: 6px; cursor: pointer;
        }
        .btn-secondary:hover { background: #555; }
        .add-btn {
            background: #4caf50; color: white; border: none; padding: 1rem 2rem;
            border-radius: 8px; cursor: pointer; font-weight: bold; margin-bottom: 2rem;
        }
        .add-btn:hover { background: #45a049; }
        
        .sidebar {
            background: #1976d2; color: #fff; width: 220px; padding: 2rem 1rem;
            display: flex; flex-direction: column; gap: 2rem; min-height: 100vh;
        }
        .sidebar h2 { color: #fff; margin-bottom: 2rem; font-size: 1.3rem; text-align: center; }
        .sidebar a {
            color: #fff; text-decoration: none; font-weight: bold; margin-bottom: 1rem;
            display: block; padding: 0.7rem 1rem; border-radius: 6px; transition: background 0.2s;
        }
        .sidebar a.active, .sidebar a:hover { background: #1565c0; }
        .main-content { flex: 1; padding: 2.5rem 3rem; background: #f3f8fd; }
        .dashboard-layout { display: flex; min-height: 100vh; }
        .logout-btn {
            background: #f44336; color: white; border: none; padding: 0.8rem 1.5rem;
            border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .logout-btn:hover { background: #d32f2f; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php" class="active">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <div class="main-content">
            <h1>Gestion des franchisés</h1>
            
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
                        <!-- Rempli par JS -->
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
                        <!-- Rempli par JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/admin/franchisés.js"></script>
    <script>
        // Fonction pour changer d'onglet
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

        // Charger tous les franchisés
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

        // Charger les comptes en attente
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
                        loadFranchises(); // Recharger aussi l'onglet principal
                    }
                    alert(`Compte ${action === 'valider' ? 'validé' : 'refusé'} avec succès!`);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la validation du compte');
            }
        }

        // Modal pour ajouter un franchisé
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
            
            // Gérer la soumission du formulaire
            document.getElementById('add-franchise-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.role = 'franchise';
                data.password = 'temp123'; // Mot de passe temporaire
                
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
            alert('Fonction de modification en cours de développement...');
        }

        function deleteFranchise(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce franchisé ?')) {
                alert('Fonction de suppression en cours de développement...');
            }
        }

        // Event listeners
        document.getElementById('add-franchise-btn').onclick = showAddFranchiseModal;

        // Fermer le modal en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });

        // Charger les données au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            loadFranchises();
            loadValidation();
        });
    </script>
</body>
</html>