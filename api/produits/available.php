<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$entrepot_id = isset($_GET['entrepot_id']) ? intval($_GET['entrepot_id']) : null;

try {
    $conn = Database::getInstance()->getConnection();
    
    if ($entrepot_id) {
        $stmt = $conn->prepare("
            SELECT p.*, s.quantite, s.unite, s.seuil_alerte,
                   CASE WHEN s.quantite > 0 THEN 1 ELSE 0 END as disponible
            FROM produits p 
            LEFT JOIN stocks s ON p.id = s.produit_id AND s.entrepot_id = ?
            ORDER BY p.obligatoire DESC, p.type, p.nom
        ");
        $stmt->execute([$entrepot_id]);
    } else {
        $stmt = $conn->query("
            SELECT p.*, 1 as disponible
            FROM produits p 
            ORDER BY p.obligatoire DESC, p.type, p.nom
        ");
    }
    
    $produits = $stmt->fetchAll();
    echo json_encode($produits);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>