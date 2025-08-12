<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\ventes\delete.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Vérifier authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $isAdmin = (isset($_SESSION['role']) ? $_SESSION['role'] : '') === 'admin';
        $userId = $_SESSION['user_id'];
        
        if ($isAdmin) {
            // ✅ Admin peut supprimer n'importe quelle vente
            $stmt = $conn->prepare("SELECT id FROM ventes WHERE id = ?");
            $stmt->execute([$data['id']]);
        } else {
            // ✅ Franchisé ne peut supprimer que ses ventes
            $stmt = $conn->prepare("SELECT id FROM ventes WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $userId]);
        }
        
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