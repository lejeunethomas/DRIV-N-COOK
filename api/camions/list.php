<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("
        SELECT c.*, u.nom as franchise_nom, u.prenom as franchise_prenom 
        FROM camions c 
        LEFT JOIN users u ON c.user_id = u.id 
        ORDER BY c.id DESC
    ");
    $camions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($camions ?: []);
} catch (Exception $e) {
    error_log('Erreur camions/list.php: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>