<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon menu - Franchisé</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .category-separator { 
            background: #f8f9fa !important; 
        }
        .plat-indisponible { 
            opacity: 0.6; 
            background: #f5f5f5; 
        }
        .badge { 
            padding: 0.2rem 0.6rem; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: bold; 
        }
        .badge-success { 
            background: #d4edda; 
            color: #155724; 
        }
        .badge-danger { 
            background: #f8d7da; 
            color: #721c24; 
        }
        .badge-warning { 
            background: #fff3cd; 
            color: #856404; 
        }
        .checkbox-group { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
    </style>
</head>
<body data-role="franchise">
    <div class="dashboard-layout">
        <nav class="sidebar franchise">
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="menu.php" class="active">Mon menu</a>
            <a href="commandes.php">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn franchise" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <main class="main-content franchise">
            <h1 style="color:#e64a19;">Gestion de mon menu</h1>
            
            <div id="alert-container"></div>
            
            <div class="section-card franchise">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2>Mon menu food truck</h2>
                        <p style="color: #666; margin: 0;">Gérez vos plats, boissons et accompagnements</p>
                    </div>
                    <button class="add-btn franchise" id="add-plat-btn">
                         + Ajouter un plat
                    </button>
                </div>

                <table id="menu-table">
                    <thead>
                        <tr>
                            <th>Nom du plat</th>
                            <th>Description & Ingrédients</th>
                            <th>Prix & Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menu-list">
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="../js/menu.js"></script>
    <script>
    const MenuManager = {
        data: [],
        async init() {
            document.getElementById('add-plat-btn').onclick = () => this.showAddModal();
            await this.loadMenus();
        },
        async loadMenus() {
            try {
                const response = await fetch('../api/menu/list.php');
                this.data = await response.json();
                this.displayMenus();
            } catch (error) {
                this.showAlert('Erreur lors du chargement du menu', 'error');
            }
        },
        displayMenus() {
            const tbody = document.getElementById('menu-list');
            tbody.innerHTML = '';
            if (!this.data || this.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#999;">Aucun plat pour le moment</td></tr>`;
                return;
            }
            this.data.forEach(plat => {
                const indispo = plat.statut === 'indisponible';
                tbody.innerHTML += `
                    <tr class="${indispo ? 'plat-indisponible' : ''}">
                        <td><strong>${plat.nom}</strong></td>
                        <td>
                            <div>${plat.description || '-'}</div>
                            <div style="color:#888; font-size:0.95em;">${plat.ingredients || ''}</div>
                        </td>
                        <td>
                            <strong>${parseFloat(plat.prix).toFixed(2)}€</strong><br>
                            <span class="badge ${plat.statut === 'disponible' ? 'badge-success' : 'badge-danger'}">
                                ${plat.statut === 'disponible' ? 'Disponible' : 'Indisponible'}
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-action" onclick="MenuManager.showEditModal(${plat.id})">Modifier</button>
                            <button class="btn-action danger" onclick="MenuManager.deletePlat(${plat.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        },
        showAddModal() {
            this.showPlatModal();
        },
        async showEditModal(id) {
            const plat = this.data.find(p => p.id == id);
            if (!plat) {
                this.showAlert('Plat introuvable', 'error');
                return;
            }
            this.showPlatModal(plat);
        },
        showPlatModal(plat = null) {
            window.showPlatModal(plat);
        },
        closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        },
        async addPlat(data) {
            await window.addPlat(data);
        },
        async updatePlat(data) {
            await window.updatePlat(data);
        },
        async deletePlat(id) {
            await window.deletePlat(id);
        },
        showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info';
            alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
            setTimeout(() => alertContainer.innerHTML = '', 5000);
        }
    };

    document.addEventListener('DOMContentLoaded', () => MenuManager.init());
    </script>
</body>
</html>