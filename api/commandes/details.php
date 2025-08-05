<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID de commande manquant']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Informations générales de la commande
    $stmt = $conn->prepare("
        SELECT c.*, 
               u.nom as franchise_nom, u.prenom as franchise_prenom, 
               u.email as franchise_email, u.telephone as franchise_telephone,
               e.nom as entrepot_nom, e.adresse as entrepot_adresse, 
               e.ville as entrepot_ville, e.code_postal as entrepot_cp,
               admin.nom as admin_nom, admin.prenom as admin_prenom
        FROM commandes c 
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN entrepots e ON c.entrepot_id = e.id
        LEFT JOIN users admin ON c.validee_par = admin.id
        WHERE c.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $commande = $stmt->fetch();
    
    if (!$commande) {
        echo json_encode(['error' => 'Commande non trouvée']);
        exit;
    }
    
    // Détails des produits commandés
    $stmt = $conn->prepare("
        SELECT cd.*, p.nom as produit_nom, p.type as produit_type,
               s.quantite as stock_disponible, s.unite as stock_unite
        FROM commande_details cd
        LEFT JOIN produits p ON cd.produit_id = p.id
        LEFT JOIN stocks s ON p.id = s.produit_id AND s.entrepot_id = ?
        WHERE cd.commande_id = ?
        ORDER BY p.type, p.nom
    ");
    $stmt->execute([$commande['entrepot_id'], $_GET['id']]);
    $details = $stmt->fetchAll();
    
    $commande['details'] = $details;
    
    echo json_encode($commande);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>