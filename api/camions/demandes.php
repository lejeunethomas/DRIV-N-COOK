<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->prepare("
        SELECT d.*, u.nom as franchise_nom, u.prenom as franchise_prenom, u.email as franchise_email 
        FROM demandes_camion d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.etat = 'en attente' 
        ORDER BY d.date_demande DESC
    ");
    $stmt->execute();
    $demandes = $stmt->fetchAll();
    
    echo json_encode($demandes);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>