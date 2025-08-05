<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    $periode = $_GET['periode'] ?? 'mois';
    $userId = $_SESSION['role'] === 'admin' ? null : $_SESSION['user_id'];
    
    $whereClause = $userId ? "WHERE v.user_id = ?" : "";
    $params = $userId ? [$userId] : [];
    
    // Statistiques générales
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as nb_ventes,
            SUM(montant) as total_ca,
            AVG(montant) as panier_moyen,
            DATE(date_vente) as date_vente
        FROM ventes v
        {$whereClause}
        AND date_vente >= DATE_SUB(NOW(), INTERVAL 1 {$periode})
        GROUP BY DATE(date_vente)
        ORDER BY date_vente DESC
    ");
    $stmt->execute($params);
    $stats = $stmt->fetchAll();
    
    // Top produits
    $stmt = $conn->prepare("
        SELECT p.nom, SUM(vd.quantite) as quantite_vendue, SUM(vd.prix_total) as ca_produit
        FROM vente_details vd
        JOIN ventes v ON vd.vente_id = v.id
        JOIN produits p ON vd.produit_id = p.id
        {$whereClause}
        AND v.date_vente >= DATE_SUB(NOW(), INTERVAL 1 {$periode})
        GROUP BY p.id
        ORDER BY quantite_vendue DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $topProduits = $stmt->fetchAll();
    
    echo json_encode([
        'stats_quotidiennes' => $stats,
        'top_produits' => $topProduits,
        'periode' => $periode
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>