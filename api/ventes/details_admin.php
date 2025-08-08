<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Récupérer la vente avec infos complètes
    $stmt = $conn->prepare("
        SELECT 
            v.*,
            u.nom as franchisé_nom,
            u.prenom as franchisé_prenom,
            c.nom_camion,
            c.emplacement as camion_localisation
        FROM ventes v
        LEFT JOIN users u ON v.user_id = u.id
        LEFT JOIN camions c ON v.camion_id = c.id
        WHERE v.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $vente = $stmt->fetch();
    
    if (!$vente) {
        echo json_encode(['error' => 'Vente non trouvée']);
        exit;
    }
    
    // Récupérer les détails
    $stmt = $conn->prepare("
        SELECT 
            vd.*,
            p.nom as produit_nom
        FROM vente_details vd
        JOIN produits p ON vd.produit_id = p.id
        WHERE vd.vente_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $details = $stmt->fetchAll();
    
    $vente['details'] = $details;
    echo json_encode($vente);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>