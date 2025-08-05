<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("
        SELECT e.*, 
               COUNT(s.id) as nb_produits_dispo,
               SUM(CASE WHEN s.quantite <= s.seuil_alerte THEN 1 ELSE 0 END) as nb_alertes
        FROM entrepots e 
        LEFT JOIN stocks s ON e.id = s.entrepot_id 
        WHERE e.actif = 1 
        GROUP BY e.id 
        ORDER BY e.nom
    ");
    $entrepots = $stmt->fetchAll();
    
    echo json_encode($entrepots);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>