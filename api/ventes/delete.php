<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(array('success' => false, 'message' => 'ID manquant'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        
        $conn->beginTransaction();
        
        // Vérifier que la vente appartient au franchisé
        $stmt = $conn->prepare("SELECT id FROM ventes WHERE id = ? AND user_id = ?");
        $stmt->execute(array($data['id'], $userId));
        
        if (!$stmt->fetch()) {
            echo json_encode(array('success' => false, 'message' => 'Vente non trouvée'));
            $conn->rollBack();
            exit;
        }
        
        // Supprimer les détails
        $stmt = $conn->prepare("DELETE FROM vente_details WHERE vente_id = ?");
        $stmt->execute(array($data['id']));
        
        // Supprimer la vente
        $stmt = $conn->prepare("DELETE FROM ventes WHERE id = ? AND user_id = ?");
        $stmt->execute(array($data['id'], $userId));
        
        $conn->commit();
        echo json_encode(array('success' => true, 'message' => 'Vente supprimée avec succès'));
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Méthode non autorisée'));
}
?>