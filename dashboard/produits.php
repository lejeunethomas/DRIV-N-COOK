<?php
require_once '../includes/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des produits - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            background: #1976d2;
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
            background: #1565c0;
        }
        .main-content {
            flex: 1;
            padding: 2.5rem 3rem;
            background: #f3f8fd;
        }
        .btn-action {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1.2rem;
            margin: 0 0.3rem;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-action:hover {
            background: #1565c0;
        }
        .btn-action.danger {
            background: #f44336;
        }
        .btn-action.danger:hover {
            background: #d32f2f;
        }
        .add-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        .add-btn:hover {
            background: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .type-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .type-aliment {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .type-boisson {
            background: #e3f2fd;
            color: #1976d2;
        }
        .type-prepare {
            background: #fff3e0;
            color: #f57c00;
        }
        .obligatoire-oui {
            color: #4caf50;
            font-weight: bold;
        }
        .obligatoire-non {
            color: #ff9800;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .modal h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #1976d2;
            outline: none;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .btn-secondary {
            background: #666;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .logout-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: #d32f2f;
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
        @media (max-width: 900px) {
            .dashboard-layout {
                flex-direction: column;
            }
            .sidebar {
                flex-direction: row;
                width: 100%;
                min-height: unset;
                padding: 1rem;
                gap: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="produits.php" class="active">Gérer les produits</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>

        <div class="main-content">
            <h1>Gestion des produits</h1>
            
            <div id="alert-container"></div>
            
            <button class="add-btn" onclick="showAddProductModal()">+ Ajouter un produit</button>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom du produit</th>
                        <th>Type</th>
                        <th>Prix unitaire</th>
                        <th>Obligatoire</th>
                        <th>Entrepôt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="products-table">
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let products = [];

        async function loadProducts() {
            try {
                const response = await fetch('../api/produits/list.php');
                products = await response.json();
                displayProducts();
            } catch (error) {
                showAlert('Erreur lors du chargement des produits', 'error');
                console.error('Erreur:', error);
            }
        }

        function displayProducts() {
            const tbody = document.getElementById('products-table');
            tbody.innerHTML = '';

            products.forEach(product => {
                const typeClass = `type-${product.type}`;
                const obligatoireClass = product.obligatoire ? 'obligatoire-oui' : 'obligatoire-non';
                const obligatoireText = product.obligatoire ? 'Oui' : 'Non';
                
                tbody.innerHTML += `
                    <tr>
                        <td>${product.id}</td>
                        <td><strong>${product.nom}</strong></td>
                        <td><span class="type-badge ${typeClass}">${product.type}</span></td>
                        <td>${parseFloat(product.prix_unitaire).toFixed(2)}€</td>
                        <td><span class="${obligatoireClass}">${obligatoireText}</span></td>
                        <td>${product.entrepot_id || 'Principal'}</td>
                        <td>
                            <button class="btn-action" onclick="editProduct(${product.id})">Modifier</button>
                            <button class="btn-action danger" onclick="deleteProduct(${product.id})">Supprimer</button>
                        </td>
                    </tr>
                `;
            });
        }

        function showAddProductModal() {
            showProductModal();
        }

        function editProduct(id) {
            const product = products.find(p => p.id == id);
            if (product) {
                showProductModal(product);
            }
        }

        function showProductModal(product = null) {
            const isEdit = product !== null;
            const title = isEdit ? 'Modifier le produit' : 'Ajouter un produit';
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>${title}</h3>
                    <form id="product-form">
                        <div class="form-group">
                            <label for="nom">Nom du produit *</label>
                            <input type="text" id="nom" name="nom" required value="${product?.nom || ''}">
                        </div>
                        <div class="form-group">
                            <label for="type">Type *</label>
                            <select id="type" name="type" required>
                                <option value="aliment" ${product?.type === 'aliment' ? 'selected' : ''}>Aliment</option>
                                <option value="boisson" ${product?.type === 'boisson' ? 'selected' : ''}>Boisson</option>
                                <option value="préparé" ${product?.type === 'préparé' ? 'selected' : ''}>Plat préparé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="prix_unitaire">Prix unitaire (€) *</label>
                            <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" required value="${product?.prix_unitaire || ''}">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="obligatoire" name="obligatoire" ${product?.obligatoire ? 'checked' : ''}>
                                <label for="obligatoire">Produit obligatoire (80% minimum)</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="entrepot_id">Entrepôt</label>
                            <input type="number" id="entrepot_id" name="entrepot_id" placeholder="ID entrepôt (optionnel)" value="${product?.entrepot_id || ''}">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                            <button type="submit" class="btn-primary">${isEdit ? 'Modifier' : 'Ajouter'}</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            document.getElementById('product-form').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);
                data.obligatoire = document.getElementById('obligatoire').checked;
                
                if (isEdit) {
                    data.id = product.id;
                    await updateProduct(data);
                } else {
                    await addProduct(data);
                }
            };
        }

        async function addProduct(data) {
            try {
                const response = await fetch('../api/produits/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit ajouté avec succès !', 'success');
                    closeModal();
                    loadProducts();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de l\'ajout', 'error');
                console.error('Erreur:', error);
            }
        }

        async function updateProduct(data) {
            try {
                const response = await fetch('../api/produits/update.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit mis à jour avec succès !', 'success');
                    closeModal();
                    loadProducts();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la mise à jour', 'error');
                console.error('Erreur:', error);
            }
        }

        async function deleteProduct(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/produits/delete.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Produit supprimé avec succès !', 'success');
                    loadProducts();
                } else {
                    showAlert('Erreur: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erreur réseau lors de la suppression', 'error');
                console.error('Erreur:', error);
            }
        }

        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) modal.remove();
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
        });
    </script>
</body>
</html>