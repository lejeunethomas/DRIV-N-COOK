<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    $whereClause = "WHERE v.user_id = ?";
    $params = [$userId];
    
    // Filtrer par période si demandé
    if (isset($_GET['periode'])) {
        switch ($_GET['periode']) {
            case 'aujourdhui':
                $whereClause .= " AND DATE(v.date_vente) = CURDATE()";
                break;
            case 'semaine':
                $whereClause .= " AND YEARWEEK(v.date_vente) = YEARWEEK(NOW())";
                break;
            case 'mois':
                $whereClause .= " AND MONTH(v.date_vente) = MONTH(NOW()) AND YEAR(v.date_vente) = YEAR(NOW())";
                break;
        }
    }
    
    $stmt = $conn->prepare("
        SELECT 
            v.id,
            v.montant,
            v.date_vente,
            v.type_paiement,
            GROUP_CONCAT(
                CONCAT(p.nom, ' (x', vd.quantite, ')')
                SEPARATOR ', '
            ) as produits_resume
        FROM ventes v
        LEFT JOIN vente_details vd ON v.id = vd.vente_id
        LEFT JOIN produits p ON vd.produit_id = p.id
        $whereClause
        GROUP BY v.id
        ORDER BY v.date_vente DESC
    ");
    
    $stmt->execute($params);
    $ventes = $stmt->fetchAll();
    
    echo json_encode($ventes);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>