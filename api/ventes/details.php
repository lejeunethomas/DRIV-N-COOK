<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    // Récupérer la vente
    $stmt = $conn->prepare("
        SELECT * FROM ventes 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$_GET['id'], $userId]);
    $vente = $stmt->fetch();
    
    if (!$vente) {
        echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
        exit;
    }
    
    // Récupérer les détails
    $stmt = $conn->prepare("
        SELECT 
            vd.*,
            p.nom
        FROM vente_details vd
        JOIN produits p ON vd.produit_id = p.id
        WHERE vd.vente_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $details = $stmt->fetchAll();
    
    $vente['details'] = $details;
    $vente['success'] = true;
    
    echo json_encode($vente);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>