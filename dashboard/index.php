<?php
require_once '../includes/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Admin - DRIV'N'COOK</title>
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
        .section-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            padding: 2rem 2.5rem;
            margin-bottom: 2.5rem;
        }
        .section-card h2 {
            color: #1976d2;
            margin-bottom: 1.2rem;
        }
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 2rem;
        }
        .logout-btn {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #1565c0;
        }
        .dashboard-cards {
            display: flex;
            gap:2rem;
            flex-wrap:wrap;
            margin-bottom:2rem;
            justify-content: center;
        }
        .cta-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            padding: 2rem 2.5rem;
            min-width: 180px;
            text-align: center;
        }
        .cta-card h2 {
            color: #1976d2;
            margin-bottom: 0.7rem;
        }
        .cta-card p {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        .dashboard-links {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .dashboard-links .btn {
            background: #1976d2;
            color: #fff;
            border-radius: 8px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.2s;
        }
        .dashboard-links .btn:hover {
            background: #1565c0;
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
            <a href="index.php" class="active">Tableau de bord</a>
            <a href="franchisés.php">Gérer les franchisés</a>
            <a href="camions.php">Gérer les camions</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content">
            <div class="topbar"></div>
            <h1 style="text-align:center; margin-bottom:2rem; color:#1976d2;">Tableau de bord Administrateur</h1>
            <div class="dashboard-cards">
                <div class="cta-card" id="stat-franchise">
                    <h2>Franchisés</h2>
                    <p id="nb-franchise">...</p>
                </div>
                <div class="cta-card" id="stat-camion">
                    <h2>Camions</h2>
                    <p id="nb-camion">...</p>
                </div>
                <div class="cta-card" id="stat-vente">
                    <h2>Ventes</h2>
                    <p id="nb-vente">...</p>
                </div>
                <div class="cta-card" id="stat-commande">
                    <h2>Commandes</h2>
                    <p id="nb-commande">...</p>
                </div>
            </div>
            <div class="dashboard-links">
                <a href="franchisés.php" class="btn">Gérer les franchisés</a>
                <a href="camions.php" class="btn">Gérer les camions</a>
                <a href="ventes.php" class="btn">Voir les ventes</a>
                <a href="commandes.php" class="btn">Voir les commandes</a>
            </div>
        </main>
    </div>
    <script>
        // Récupère les stats via API
        fetch('../api/users/get_all.php')
            .then(res => res.json())
            .then(data => document.getElementById('nb-franchise').textContent = data.length);

        fetch('../api/camions/get_by_user.php?admin=1')
            .then(res => res.json())
            .then(data => document.getElementById('nb-camion').textContent = data.length);

        fetch('../api/ventes/add.php?stats=1')
            .then(res => res.json())
            .then(data => document.getElementById('nb-vente').textContent = data.total ?? '0');

        fetch('../api/commandes/list_by_user.php?admin=1')
            .then(res => res.json())
            .then(data => document.getElementById('nb-commande').textContent = data.length);
    </script>
</body>
</html>