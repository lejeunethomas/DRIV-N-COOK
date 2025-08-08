<?php
// filepath: c:\Users\tlejeune\Documents\GitHub\DRIV-N-COOK\api\ventes\delete_admin.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin(); 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT id FROM ventes WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
            $conn->rollBack();
            exit;
        }
        
        // Supprimer les détails
        $stmt = $conn->prepare("DELETE FROM vente_details WHERE vente_id = ?");
        $stmt->execute([$data['id']]);
        
        // Supprimer la vente
        $stmt = $conn->prepare("DELETE FROM ventes WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vente supprimée avec succès']);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>