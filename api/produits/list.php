<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("
        SELECT id, nom, type, prix_unitaire, obligatoire, entrepot_id 
        FROM produits 
        ORDER BY type, nom
    ");
    $produits = $stmt->fetchAll();
    
    echo json_encode($produits);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>