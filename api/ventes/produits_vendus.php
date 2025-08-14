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
            (
                SELECT vd2.prix_unitaire
                FROM vente_details vd2
                WHERE vd2.produit_id = p.id
                ORDER BY vd2.vente_id DESC
                LIMIT 1
            ) as unite,
            SUM(vd.quantite) as quantite_totale,
            SUM(vd.prix_total) as ca_total,
            COUNT(DISTINCT vd.vente_id) as nb_ventes
        FROM vente_details vd
        JOIN produits p ON vd.produit_id = p.id
        JOIN ventes v ON vd.vente_id = v.id
        GROUP BY p.id, p.nom
        ORDER BY quantite_totale DESC
    ");
    
    $produits = $stmt->fetchAll();
    echo json_encode($produits);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>