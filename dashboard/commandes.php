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
            <a href="camions.php">Gérer les camions</a>
            <a href="ventes.php">Voir les ventes</a>
            <a href="commandes.php" class="active">Voir les commandes</a>
            <form action="../api/users/logout.php" method="post" style="margin-top:auto;">
                <button type="submit" class="logout-btn" style="width:100%;">Déconnexion</button>
            </form>
        </nav>