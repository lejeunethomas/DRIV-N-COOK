<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$entrepot_id = isset($_GET['entrepot_id']) ? intval($_GET['entrepot_id']) : null;

try {
    $conn = Database::getInstance()->getConnection();
    
    $whereClause = $entrepot_id ? "WHERE s.entrepot_id = ?" : "";
    $params = $entrepot_id ? [$entrepot_id] : [];
    
    $stmt = $conn->prepare("
        SELECT s.*, p.nom as produit_nom, p.type as produit_type, e.nom as entrepot_nom,
               CASE WHEN s.quantite <= s.seuil_alerte THEN 1 ELSE 0 END as alerte
        FROM stocks s 
        JOIN produits p ON s.produit_id = p.id 
        JOIN entrepots e ON s.entrepot_id = e.id 
        {$whereClause}
        ORDER BY e.nom, p.type, p.nom
    ");
    $stmt->execute($params);
    $stocks = $stmt->fetchAll();
    
    echo json_encode($stocks);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>