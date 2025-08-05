<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    // Ventes du jour
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total
        FROM ventes 
        WHERE user_id = ? AND DATE(date_vente) = CURDATE()
    ");
    $stmt->execute([$userId]);
    $ventesJour = $stmt->fetch();
    
    // Ventes de la semaine
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total
        FROM ventes 
        WHERE user_id = ? AND YEARWEEK(date_vente) = YEARWEEK(NOW())
    ");
    $stmt->execute([$userId]);
    $ventesSemaine = $stmt->fetch();
    
    // Ventes du mois
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total
        FROM ventes 
        WHERE user_id = ? 
        AND MONTH(date_vente) = MONTH(NOW())
        AND YEAR(date_vente) = YEAR(NOW())
    ");
    $stmt->execute([$userId]);
    $ventesMois = $stmt->fetch();
    
    // Ventes totales
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total
        FROM ventes 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $ventesTotal = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'ventes_jour' => floatval($ventesJour['total']),
        'ventes_semaine' => floatval($ventesSemaine['total']),
        'total_mois' => floatval($ventesMois['total']),
        'total_general' => floatval($ventesTotal['total'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
?>