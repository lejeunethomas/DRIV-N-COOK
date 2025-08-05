<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    if ($_SESSION['role'] === 'admin') {
        // Admin voit toutes les ventes
        $stmt = $conn->query("
            SELECT v.*, u.nom as franchise_nom, u.prenom as franchise_prenom,
                   c.nom_camion, c.immatriculation,
                   COUNT(vd.id) as nb_produits
            FROM ventes v
            LEFT JOIN users u ON v.user_id = u.id
            LEFT JOIN camions c ON v.camion_id = c.id
            LEFT JOIN vente_details vd ON v.id = vd.vente_id
            GROUP BY v.id
            ORDER BY v.date_vente DESC
        ");
    } else {
        // Franchisé voit ses ventes
        $stmt = $conn->prepare("
            SELECT v.*, c.nom_camion, c.immatriculation,
                   COUNT(vd.id) as nb_produits
            FROM ventes v
            LEFT JOIN camions c ON v.camion_id = c.id
            LEFT JOIN vente_details vd ON v.id = vd.vente_id
            WHERE v.user_id = ?
            GROUP BY v.id
            ORDER BY v.date_vente DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    $ventes = $stmt->fetchAll();
    echo json_encode($ventes);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>