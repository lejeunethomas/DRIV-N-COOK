<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon compte - Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            background: #ff5722;
            color: #fff;
            width: 220px;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            min-height: 100vh;
        }
        .sidebar h2 {
            color: #fff;
            margin-bottom: 2rem;
            font-size: 1.3rem;
            text-align: center;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 1rem;
            display: block;
            padding: 0.7rem 1rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #e64a19;
        }
        .main-content {
            flex: 1;
            padding: 2.5rem 3rem;
            background: #fff8f0;
        }
        .section-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            padding: 2rem 2.5rem;
            margin-bottom: 2.5rem;
        }
        .section-card h2 {
            color: #ff5722;
            margin-bottom: 1.2rem;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .logout-btn {
            background: #ff5722;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #e64a19;
        }
        .profile-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .profile-field {
            display: flex;
            flex-direction: column;
        }
        .profile-field.full-width {
            grid-column: span 2;
        }
        .profile-field label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .profile-field .value {
            padding: 0.7rem;
            background: #f8f8f8;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .profile-field input,
        .profile-field textarea {
            padding: 0.7rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-family: inherit;
        }
        .profile-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .edit-mode {
            display: none;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #ff5722;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #ff5722;
        }
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        @media (max-width: 900px) {
            .dashboard-layout { flex-direction: column; }
            .sidebar { flex-direction: row; width: 100%; min-height: unset; padding: 1rem; gap: 1rem;}
            .main-content { padding: 1rem; }
            .profile-section { grid-template-columns: 1fr; }
            .profile-field.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php" class="active">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content">
            <div class="topbar">
                <h1 style="margin:0; color:#e64a19;">Mon compte</h1>
                <button id="edit-btn" class="btn" onclick="toggleEditMode()">Modifier</button>
            </div>

            <div id="alert-container"></div>

            <div class="section-card">
                <h2>Informations personnelles</h2>
                
                <!-- Mode consultation -->
                <div id="view-mode">
                    <div class="profile-section">
                        <div class="profile-field">
                            <label>Nom</label>
                            <div class="value" id="view-nom">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Prénom</label>
                            <div class="value" id="view-prenom">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Email</label>
                            <div class="value" id="view-email">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Téléphone</label>
                            <div class="value" id="view-telephone">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Numéro de permis</label>
                            <div class="value" id="view-numero-permis">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Lieu d'installation souhaité</label>
                            <div class="value" id="view-lieu">-</div>
                        </div>
                        <div class="profile-field full-width">
                            <label>Motivation</label>
                            <div class="value" id="view-motivation">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Date d'inscription</label>
                            <div class="value" id="view-date">-</div>
                        </div>
                    </div>
                </div>

                <!-- Mode édition -->
                <div id="edit-mode" class="edit-mode">
                    <form id="profile-form">
                        <div class="profile-section">
                            <div class="profile-field">
                                <label for="edit-nom">Nom *</label>
                                <input type="text" id="edit-nom" required>
                            </div>
                            <div class="profile-field">
                                <label for="edit-prenom">Prénom *</label>
                                <input type="text" id="edit-prenom" required>
                            </div>
                            <div class="profile-field">
                                <label for="edit-telephone">Téléphone *</label>
                                <input type="tel" id="edit-telephone" required placeholder="Ex: 06 12 34 56 78">
                            </div>
                            <div class="profile-field">
                                <label for="edit-numero-permis">Numéro de permis</label>
                                <input type="text" id="edit-numero-permis" placeholder="Ex: 123456789012">
                            </div>
                            <div class="profile-field full-width">
                                <label for="edit-lieu">Lieu d'installation souhaité *</label>
                                <input type="text" id="edit-lieu" required placeholder="Ex: Paris, Lyon, Marseille...">
                            </div>
                            <div class="profile-field full-width">
                                <label for="edit-motivation">Motivation *</label>
                                <textarea id="edit-motivation" required rows="3" placeholder="Décrivez en quelques lignes votre motivation..."></textarea>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn">Sauvegarder</button>
                            <button type="button" class="btn-secondary" onclick="cancelEdit()">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="section-card">
                <h2>Statistiques</h2>
                <div class="stats-section">
                    <div class="stat-card">
                        <div class="stat-value" id="stat-camions">0</div>
                        <div class="stat-label">Camions actifs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-ventes">0€</div>
                        <div class="stat-label">Ventes ce mois</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-commandes">0</div>
                        <div class="stat-label">Commandes en cours</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let userProfile = null;

        // Charger le profil utilisateur
        async function loadProfile() {
            try {
                const response = await fetch('../api/users/profile.php');
                const data = await response.json();
                
                if (data.success === false) {
                    showAlert('Erreur lors du chargement du profil: ' + data.message, 'error');
                    return;
                }
                
                userProfile = data;
                displayProfile(data);
            } catch (error) {
                showAlert('Erreur réseau lors du chargement du profil', 'error');
                console.error('Erreur:', error);
            }
        }

        // Afficher les données du profil
        function displayProfile(profile) {
            document.getElementById('view-nom').textContent = profile.nom || '-';
            document.getElementById('view-prenom').textContent = profile.prenom || '-';
            document.getElementById('view-email').textContent = profile.email || '-';
            document.getElementById('view-telephone').textContent = profile.telephone || '-';
            document.getElementById('view-numero-permis').textContent = profile.numero_permis || 'Non renseigné';
            document.getElementById('view-lieu').textContent = profile.lieu_installation || '-';
            document.getElementById('view-motivation').textContent = profile.motivation || '-';
            document.getElementById('view-date').textContent = profile.date_inscription ? new Date(profile.date_inscription).toLocaleDateString('fr-FR') : '-';
        }

        // Activer le mode édition
        function toggleEditMode() {
            document.getElementById('view-mode').style.display = 'none';
            document.getElementById('edit-mode').style.display = 'block';
            document.getElementById('edit-btn').style.display = 'none';
            
            // Pré-remplir les champs
            if (userProfile) {
                document.getElementById('edit-nom').value = userProfile.nom || '';
                document.getElementById('edit-prenom').value = userProfile.prenom || '';
                document.getElementById('edit-telephone').value = userProfile.telephone || '';
                document.getElementById('edit-numero-permis').value = userProfile.numero_permis || '';
                document.getElementById('edit-lieu').value = userProfile.lieu_installation || '';
                document.getElementById('edit-motivation').value = userProfile.motivation || '';
            }
        }

        // Annuler l'édition
        function cancelEdit() {
            document.getElementById('view-mode').style.display = 'block';
            document.getElementById('edit-mode').style.display = 'none';
            document.getElementById('edit-btn').style.display = 'block';
        }

        // Sauvegarder les modifications
        async function saveProfile(formData) {
            try {
                const response = await fetch('../api/users/profile.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Profil mis à jour avec succès !', 'success');
                    userProfile = { ...userProfile, ...formData };
                    displayProfile(userProfile);
                    cancelEdit();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la sauvegarde', 'error');
                console.error('Erreur:', error);
            }
        }

        // Afficher une alerte
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            // Masquer l'alerte après 5 secondes
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        // Charger les statistiques
        async function loadStats() {
            try {
                // Ici vous pouvez implémenter les appels API pour récupérer les statistiques
                // document.getElementById('stat-camions').textContent = '1';
                // document.getElementById('stat-ventes').textContent = '1250€';
                // document.getElementById('stat-commandes').textContent = '3';
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
            }
        }

        // Gestion du formulaire
        document.getElementById('profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                nom: document.getElementById('edit-nom').value,
                prenom: document.getElementById('edit-prenom').value,
                telephone: document.getElementById('edit-telephone').value,
                numero_permis: document.getElementById('edit-numero-permis').value,
                lieu_installation: document.getElementById('edit-lieu').value,
                motivation: document.getElementById('edit-motivation').value
            };
            
            await saveProfile(formData);
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            loadProfile();
            loadStats();
        });
    </script>
</body>
</html>