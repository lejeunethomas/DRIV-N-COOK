<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            id,
            nom,
            type,
            prix_unitaire,
            obligatoire,
            unite
        FROM produits 
        WHERE actif = 1
        ORDER BY obligatoire DESC, nom ASC
    ");
    
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produits);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>