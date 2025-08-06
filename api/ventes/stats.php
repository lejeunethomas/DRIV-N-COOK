<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('error' => 'Non authentifié'));
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    $periode = isset($_GET['periode']) ? $_GET['periode'] : 'mois';
    $userId = $_SESSION['role'] === 'admin' ? null : $_SESSION['user_id'];
    
    $whereClause = $userId ? "WHERE v.user_id = ?" : "";
    $params = $userId ? array($userId) : array();
    
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
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>