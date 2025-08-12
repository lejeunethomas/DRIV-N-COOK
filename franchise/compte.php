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
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="menu.php">Mon menu</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php" class="active">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content franchise">
            <div class="topbar">
                <h1 style="margin:0; color:#e64a19;">Mon compte</h1>
                <button id="edit-btn" class="btn" onclick="toggleEditMode()">Modifier</button>
            </div>

            <div id="alert-container"></div>

            <div class="section-card franchise">
                <h2>Informations personnelles</h2>
                
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
                        <div class="stat-label">Mon camion</div>
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

    <script src="../js/admin/common.js"></script>

    <script>
        let userProfile = null;

        function showAlert(message, type) {
            AdminCommon.utils.showAlert(message, type);
        }

        function displayProfile(profile) {
            document.getElementById('view-nom').textContent = profile.nom || '-';
            document.getElementById('view-prenom').textContent = profile.prenom || '-';
            document.getElementById('view-email').textContent = profile.email || '-';
            document.getElementById('view-telephone').textContent = profile.telephone || '-';
            document.getElementById('view-numero-permis').textContent = profile.numero_permis || 'Non renseigné';
            document.getElementById('view-lieu').textContent = profile.lieu_installation || '-';
            document.getElementById('view-motivation').textContent = profile.motivation || '-';
            document.getElementById('view-date').textContent = profile.date_inscription ? 
                AdminCommon.utils.formatDate(profile.date_inscription) : '-';
        }

        async function loadStats() {
            try {
                const camionsRes = await fetch('../api/camions/get_by_user.php');
                const camions = await camionsRes.json();
                const nbCamions = Array.isArray(camions) && camions.length > 0 ? 1 : 0;
                document.getElementById('stat-camions').textContent = nbCamions;
                
                document.querySelector('#stat-camions').parentElement.querySelector('.stat-label').textContent = 
                    nbCamions > 0 ? 'Mon camion' : 'Aucun camion';

                const commandesRes = await fetch('../api/commandes/list_by_user.php');
                const commandes = await commandesRes.json();
                const commandesEnCours = Array.isArray(commandes) ? 
                    commandes.filter(c => c.statut === 'en_attente' || c.statut === 'validee').length : 0;
                document.getElementById('stat-commandes').textContent = commandesEnCours;

                try {
                    const ventesRes = await fetch('../api/ventes/stats.php');
                    const ventes = await ventesRes.json();
                    
                    if (ventes.success) {
                        document.getElementById('stat-ventes').textContent = ventes.total_mois + '€';
                    }
                } catch (error) {
                    console.error('Erreur ventes:', error);
                }
                
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
                document.getElementById('stat-camions').textContent = 'Erreur';
                document.getElementById('stat-ventes').textContent = 'Erreur';
                document.getElementById('stat-commandes').textContent = 'Erreur';
            }
        }

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

        function toggleEditMode() {
            document.getElementById('view-mode').style.display = 'none';
            document.getElementById('edit-mode').style.display = 'block';
            document.getElementById('edit-btn').style.display = 'none';
            
            if (userProfile) {
                document.getElementById('edit-nom').value = userProfile.nom || '';
                document.getElementById('edit-prenom').value = userProfile.prenom || '';
                document.getElementById('edit-telephone').value = userProfile.telephone || '';
                document.getElementById('edit-numero-permis').value = userProfile.numero_permis || '';
                document.getElementById('edit-lieu').value = userProfile.lieu_installation || '';
                document.getElementById('edit-motivation').value = userProfile.motivation || '';
            }
        }

        function cancelEdit() {
            document.getElementById('view-mode').style.display = 'block';
            document.getElementById('edit-mode').style.display = 'none';
            document.getElementById('edit-btn').style.display = 'block';
        }

        async function saveProfile(formData) {
            try {
                const result = await AdminCommon.utils.apiRequest('../api/users/profile.php', {
                    method: 'PUT',
                    data: formData,
                    showLoader: true
                });
                
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
            }
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            loadProfile();
            loadStats();
        });
    </script>
</body>
</html>