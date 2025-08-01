<?php
require_once '../includes/auth.php';
require_franchise_validated(); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Franchisé</title>
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
            justify-content: flex-end;
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
            <h2>Mon espace</h2>
            <a href="index.php">Tableau de bord</a>
            <a href="ventes.php">Mes ventes</a>
            <a href="commandes.php" class="active">Commandes de stock</a>
            <a href="compte.php">Mon compte</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>
        <main class="main-content">
            <div class="topbar"></div>
            <div class="section-card">
                <h2>Commandes de stock</h2>
                <p>Gérez vos commandes de stock ici.</p>
                <!-- Add your content for stock orders here -->
            </div>
        </main>
    </div>
</body>
</html>
