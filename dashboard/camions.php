<?php
require_once '../includes/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des camions - Admin</title>
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
        .accordion-row { cursor: pointer; transition: background 0.2s; }
        .accordion-row:hover { background: #f3f8fd; }
        .accordion-details { display: none; background: #f9f9f9; }
        .accordion-details.open { display: table-row; }
        .camion-urgence { background: #fff3e0 !important; }
        .camion-ok { color: #388e3c; font-weight: bold; }
        .camion-maintenance { color: #e64a19; font-weight: bold; }
        .date-livraison { font-size: 0.95em; color: #1976d2; }
        .btn-action { background: #1976d2; color: #fff; border: none; border-radius: 6px; padding: 0.5rem 1.2rem; margin: 0 0.3rem; cursor: pointer; }
        .btn-action:hover { background: #1565c0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th, td { padding: 0.7rem 1rem; border-bottom: 1px solid #eee; }
        th { background: #f3f8fd; }
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
        @media (max-width: 900px) {
            .dashboard-layout { flex-direction: column; }
            .sidebar { flex-direction: row; width: 100%; min-height: unset; padding: 1rem; gap: 1rem;}
            .main-content { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <nav class="sidebar">
            <h2>Admin</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php" class="active">Gérer les camions</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content">
            <h1>Gestion des camions</h1>
            <div class="tabs">
                <button class="tab-btn active" id="tab-camions-btn" onclick="switchTab('camions')">
                    Parc de camions
                </button>
                <button class="tab-btn" id="tab-demandes-btn" onclick="switchTab('demandes')">
                    Demandes à traiter <span class="badge" id="badge-demandes" style="display:none;">0</span>
                </button>
            </div>

            <div id="tab-camions" class="tab-content">
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
                        <!-- Rempli dynamiquement via JS -->
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
                        <!-- Rempli dynamiquement via JS -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
    // Gestion des onglets
    function switchTab(tab) {
        document.getElementById('tab-camions').style.display = tab === 'camions' ? '' : 'none';
        document.getElementById('tab-demandes').style.display = tab === 'demandes' ? '' : 'none';
        document.getElementById('tab-camions-btn').classList.toggle('active', tab === 'camions');
        document.getElementById('tab-demandes-btn').classList.toggle('active', tab === 'demandes');
    }

    // Remplissage dynamique
    document.addEventListener('DOMContentLoaded', async function() {
        // 1. Charger les demandes de camions
        const demandesRes = await fetch('../api/camions/demandes.php');
        const demandes = await demandesRes.json();
        document.getElementById('badge-demandes').textContent = demandes.length;
        document.getElementById('badge-demandes').style.display = demandes.length ? '' : 'none';
        const tbodyDem = document.getElementById('liste-demandes-camion');
        demandes.forEach(demande => {
            tbodyDem.innerHTML += `
                <tr>
                    <td>${demande.franchise_nom} ${demande.franchise_prenom}</td>
                    <td><strong>${demande.nom_camion}</strong></td>
                    <td>${demande.numero_permis}</td>
                    <td>${demande.emplacement}</td>
                    <td>${demande.menu}</td>
                    <td>${demande.jours}</td>
                    <td>${demande.date_demande}</td>
                    <td>
                        <button class="btn-action" onclick="validerDemande(${demande.id})">Valider</button>
                        <button class="btn-action" onclick="refuserDemande(${demande.id})">Refuser</button>
                    </td>
                </tr>
            `;
        });
        
        // 2. Charger les camions
        const camionsRes = await fetch('../api/camions/list.php');
        let camions = await camionsRes.json();

        // Tri : urgences/maintenance/demande en haut, puis alphabétique
        camions.sort((a, b) => {
            // Priorité à ceux avec demande maintenance ou urgence
            if ((b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0) !== 0)
                return (b.demande_maintenance ? 1 : 0) - (a.demande_maintenance ? 1 : 0);
            // Sinon tri alphabétique sur le nom du franchisé
            return a.franchise_nom.localeCompare(b.franchise_nom);
        });

        const tbody = document.getElementById('liste-camions');
        camions.forEach(camion => {
            const urgence = camion.demande_maintenance ? 'camion-urgence' : '';
            tbody.innerHTML += `
                <tr class="accordion-row ${urgence}" onclick="toggleDetails(this)">
                    <td>${camion.id}</td>
                    <td>${camion.franchise_nom}</td>
                    <td>${camion.etat}</td>
                    <td>${camion.emplacement}</td>
                    <td>${camion.prochaine_maintenance ?? '-'}</td>
                    <td>
                        ${camion.demande_maintenance 
                            ? '<span class="camion-maintenance">À traiter</span>' 
                            : '<span class="camion-ok">OK</span>'}
                    </td>
                    <td>
                        ${camion.date_livraison 
                            ? '<span class="date-livraison">'+camion.date_livraison+'</span>' 
                            : '-'}
                    </td>
                </tr>
                <tr class="accordion-details">
                    <td colspan="7">
                        <strong>Détails du camion #${camion.id}</strong><br>
                        Menu : ${camion.menu ?? '-'}<br>
                        Jours d'ouverture : ${camion.jours ?? '-'}<br>
                        Historique entretiens : ${camion.historique_entretiens ?? '-'}<br>
                        <button class="btn-action">Modifier</button>
                        <button class="btn-action">Maintenance</button>
                    </td>
                </tr>
            `;
        });
    });

    // Accordion/dépliage
    function toggleDetails(row) {
        const next = row.nextElementSibling;
        if (next && next.classList.contains('accordion-details')) {
            next.classList.toggle('open');
        }
    }

    // Fonctions à implémenter pour traiter les demandes
    function validerDemande(id) {
        // Appel API pour valider la demande, puis reload la page/tableau
        alert("Valider la demande #" + id);
    }
    function refuserDemande(id) {
        // Appel API pour refuser la demande, puis reload la page/tableau
        alert("Refuser la demande #" + id);
    }
    </script>
</body>
</html>