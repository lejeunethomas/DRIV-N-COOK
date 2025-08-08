<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    // Total général
    $stmt = $conn->query("SELECT COALESCE(SUM(montant), 0) as total FROM ventes");
    $totalGeneral = $stmt->fetch()['total'];
    
    // Aujourd'hui
    $stmt = $conn->query("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE DATE(date_vente) = CURDATE()
    ");
    $aujourdhui = $stmt->fetch()['total'];
    
    // Cette semaine
    $stmt = $conn->query("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE YEARWEEK(date_vente) = YEARWEEK(NOW())
    ");
    $semaine = $stmt->fetch()['total'];
    
    // Ce mois
    $stmt = $conn->query("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE MONTH(date_vente) = MONTH(NOW()) 
        AND YEAR(date_vente) = YEAR(NOW())
    ");
    $mois = $stmt->fetch()['total'];
    
    echo json_encode([
        'total' => floatval($totalGeneral),
        'aujourdhui' => floatval($aujourdhui),
        'semaine' => floatval($semaine),
        'mois' => floatval($mois)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>