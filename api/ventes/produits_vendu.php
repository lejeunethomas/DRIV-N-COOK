<?php
// filepath: c:\Users\tlejeune\Documents\GitHub\DRIV-N-COOK\api\ventes\produits_vendus.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            p.nom,
            p.unite,
            SUM(vd.quantite) as quantite_totale,
            SUM(vd.prix_total) as ca_total,
            COUNT(DISTINCT vd.vente_id) as nb_ventes
        FROM vente_details vd
        JOIN produits p ON vd.produit_id = p.id
        JOIN ventes v ON vd.vente_id = v.id
        GROUP BY p.id, p.nom, p.unite
        ORDER BY quantite_totale DESC
    ");
    
    $produits = $stmt->fetchAll();
    echo json_encode($produits);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>