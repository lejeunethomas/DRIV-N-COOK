<?php
require_once '../includes/auth.php';
require_role('client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Client</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar client">
            <h2>Mon Tableau de bord</h2>
            <a href="index.php">Accueil</a>
            <a href="commander.php">Commander</a>
            <a href="mes_commandes.php">Mes commandes</a>
            <a href="compte.php" class="active">Mon profil</a>
            <a href="newsletter.php">Newsletter</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content client">
            <div class="topbar">
                <h1 class="client">Mon Profil</h1>
                <button id="edit-btn" class="btn-primary" onclick="CompteManager.toggleEditMode()">Modifier</button>
            </div>

            <div id="alert-container"></div>

            <div class="section-card client">
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
                            <label>Date d'inscription</label>
                            <div class="value" id="view-date">-</div>
                        </div>
                        <div class="profile-field">
                            <label>Points fidélité</label>
                            <div class="value" id="view-points">-</div>
                        </div>
                    </div>
                </div>

                <!-- Mode édition -->
                <div id="edit-mode" class="edit-mode" style="display:none;">
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
                                <label for="edit-email">Email *</label>
                                <input type="email" id="edit-email" required readonly 
                                       title="L'email ne peut pas être modifié">
                            </div>
                            <div class="profile-field">
                                <label for="edit-telephone">Téléphone</label>
                                <input type="tel" id="edit-telephone" placeholder="Ex: 06 12 34 56 78">
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="submit" class="btn-success">Sauvegarder</button>
                            <button type="button" class="btn-secondary" onclick="CompteManager.cancelEdit()">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="section-card client">
                <h2>Statistiques</h2>
                <div class="stats-section">
                    <div class="stat-card">
                        <div class="stat-value" id="stat-commandes">0</div>
                        <div class="stat-label">Commandes passées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-points">0</div>
                        <div class="stat-label">Points de fidélité</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="stat-economie">0€</div>
                        <div class="stat-label">Économies réalisées</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../js/admin/common.js"></script>
    <script>
    const CompteManager = {
        userProfile: null,

        showAlert(message, type) {
            if (window.AdminCommon && AdminCommon.utils && AdminCommon.utils.showAlert) {
                AdminCommon.utils.showAlert(message, type);
            } else {
                const alertContainer = document.getElementById('alert-container');
                const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
                alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
                setTimeout(() => { alertContainer.innerHTML = ''; }, 5000);
            }
        },

        displayProfile(profile) {
            document.getElementById('view-nom').textContent = profile.nom || '-';
            document.getElementById('view-prenom').textContent = profile.prenom || '-';
            document.getElementById('view-email').textContent = profile.email || '-';
            document.getElementById('view-telephone').textContent = profile.telephone || 'Non renseigné';
            document.getElementById('view-date').textContent = profile.date_inscription
                ? (window.AdminCommon && AdminCommon.utils && AdminCommon.utils.formatDate
                    ? AdminCommon.utils.formatDate(profile.date_inscription)
                    : new Date(profile.date_inscription).toLocaleDateString('fr-FR'))
                : '-';
            document.getElementById('view-points').textContent = profile.points_fidelite !== undefined
                ? profile.points_fidelite
                : (profile.points || '0');
        },

        toggleEditMode() {
            document.getElementById('view-mode').style.display = 'none';
            document.getElementById('edit-mode').style.display = 'block';
            document.getElementById('edit-btn').style.display = 'none';
            if (this.userProfile) {
                document.getElementById('edit-nom').value = this.userProfile.nom || '';
                document.getElementById('edit-prenom').value = this.userProfile.prenom || '';
                document.getElementById('edit-email').value = this.userProfile.email || '';
                document.getElementById('edit-telephone').value = this.userProfile.telephone || '';
            }
        },

        cancelEdit() {
            document.getElementById('view-mode').style.display = 'block';
            document.getElementById('edit-mode').style.display = 'none';
            document.getElementById('edit-btn').style.display = 'block';
        },

        async saveProfile(formData) {
            try {
                const response = await fetch('../api/users/update_profile.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });
                const result = await response.json();
                if (result.success) {
                    this.showAlert('Profil mis à jour avec succès !', 'success');
                    this.userProfile = { ...this.userProfile, ...formData };
                    this.displayProfile(this.userProfile);
                    this.cancelEdit();
                } else {
                    this.showAlert('Erreur lors de la mise à jour : ' + result.message, 'error');
                }
            } catch (error) {
                this.showAlert('Erreur réseau lors de la sauvegarde', 'error');
            }
        },

        async loadProfile() {
            try {
                const response = await fetch('../api/users/profile.php');
                const data = await response.json();
                if (data.success) {
                    this.userProfile = data.profile || data;
                    this.displayProfile(this.userProfile);
                } else {
                    this.showAlert('Erreur lors du chargement du profil : ' + data.message, 'error');
                }
            } catch (error) {
                this.showAlert('Erreur réseau lors du chargement du profil', 'error');
            }
        },

        async loadStats() {
            try {
                const response = await fetch('../api/ventes/stats.php');
                const stats = await response.json();
                document.getElementById('stat-commandes').textContent = stats.nb_commandes !== undefined
                    ? stats.nb_commandes : '0';
                document.getElementById('stat-points').textContent = stats.nb_commandes !== undefined
                    ? stats.nb_commandes : '0';
                document.getElementById('stat-economie').textContent = stats.total_general !== undefined
                    ? (window.AdminCommon && AdminCommon.utils && AdminCommon.utils.formatPrice
                        ? AdminCommon.utils.formatPrice(stats.total_general)
                        : parseFloat(stats.total_general).toFixed(2) + '€')
                    : '0€';
            } catch (error) {
                document.getElementById('stat-commandes').textContent = '0';
                document.getElementById('stat-points').textContent = '0';
                document.getElementById('stat-economie').textContent = '0€';
            }
        },

        init() {
            this.loadProfile();
            this.loadStats();
            document.getElementById('profile-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = {
                    nom: document.getElementById('edit-nom').value,
                    prenom: document.getElementById('edit-prenom').value,
                    telephone: document.getElementById('edit-telephone').value
                };
                await this.saveProfile(formData);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => CompteManager.init());
    </script>
</body>
</html>
