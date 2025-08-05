<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id']) || empty($data['nom']) || empty($data['prix'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        
        // Vérifier que le plat appartient au franchisé
        $stmt = $conn->prepare("SELECT id FROM menus WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $userId]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Plat non trouvé']);
            exit;
        }
        
        $stmt = $conn->prepare("
            UPDATE menus 
            SET nom = ?, description = ?, prix = ?, categorie = ?, ingredients = ?, allergenes = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $data['nom'],
            $data['description'] ?? '',
            $data['prix'],
            $data['categorie'] ?? 'plat',
            $data['ingredients'] ?? '',
            $data['allergenes'] ?? '',
            $data['id'],
            $userId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Plat mis à jour avec succès']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>