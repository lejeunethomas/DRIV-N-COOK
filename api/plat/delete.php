<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        
        // Vérifier que le plat appartient au franchisé
        $stmt = $conn->prepare("SELECT nom FROM menus WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $userId]);
        $plat = $stmt->fetch();
        
        if (!$plat) {
            echo json_encode(['success' => false, 'message' => 'Plat non trouvé']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM menus WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Plat supprimé avec succès']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>