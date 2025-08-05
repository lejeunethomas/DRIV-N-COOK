<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT nom_camion, date_demande 
        FROM demandes_camion 
        WHERE user_id = ? AND etat = 'en attente'
        ORDER BY date_demande DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $demande = $stmt->fetch();
    
    if ($demande) {
        echo json_encode([
            'has_pending' => true,
            'nom_camion' => $demande['nom_camion'],
            'date_demande' => $demande['date_demande']
        ]);
    } else {
        echo json_encode(['has_pending' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['has_pending' => false, 'error' => $e->getMessage()]);
}
?>