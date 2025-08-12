<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\ventes\stats.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$isAdmin = (isset($_SESSION['role']) ? $_SESSION['role'] : '') === 'admin';
$userId = $_SESSION['user_id'];
$params = $_GET;

try {
    $conn = Database::getInstance()->getConnection();
    
    if ($isAdmin && isset($params['user_id'])) {
        getStatsByUser($conn, $params['user_id']);
    } elseif ($isAdmin && isset($params['global'])) {
        getGlobalStats($conn);
    } else {
        getStatsByUser($conn, $userId);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}

function getStatsByUser($conn, $userId) {
    // Total général
    $stmt = $conn->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM ventes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalGeneral = $stmt->fetch()['total'];
    
    // Aujourd'hui
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE user_id = ? AND DATE(date_vente) = CURDATE()
    ");
    $stmt->execute([$userId]);
    $aujourdhui = $stmt->fetch()['total'];
    
    // Cette semaine
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE user_id = ? AND YEARWEEK(date_vente) = YEARWEEK(NOW())
    ");
    $stmt->execute([$userId]);
    $semaine = $stmt->fetch()['total'];
    
    // Ce mois
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(montant), 0) as total 
        FROM ventes 
        WHERE user_id = ? AND MONTH(date_vente) = MONTH(NOW()) AND YEAR(date_vente) = YEAR(NOW())
    ");
    $stmt->execute([$userId]);
    $mois = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'total_general' => floatval($totalGeneral),
        'ventes_jour' => floatval($aujourdhui),
        'ventes_semaine' => floatval($semaine),
        'total_mois' => floatval($mois)
    ]);
}

function getGlobalStats($conn) {
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
        WHERE MONTH(date_vente) = MONTH(NOW()) AND YEAR(date_vente) = YEAR(NOW())
    ");
    $mois = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'total_general' => floatval($totalGeneral),
        'ventes_jour' => floatval($aujourdhui),
        'ventes_semaine' => floatval($semaine),
        'total_mois' => floatval($mois)
    ]);
}
?>