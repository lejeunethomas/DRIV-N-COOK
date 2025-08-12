<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\camions\sync_emplacement.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['camion_id']) || empty($data['emplacement'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT user_id FROM camions WHERE id = ?");
        $stmt->execute([$data['camion_id']]);
        $camion = $stmt->fetch();
        
        if (!$camion) {
            echo json_encode(['success' => false, 'message' => 'Camion non trouvé']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE camions SET emplacement = ? WHERE id = ?");
        $stmt->execute([$data['emplacement'], $data['camion_id']]);
        
        $stmt = $conn->prepare("UPDATE users SET lieu_installation = ? WHERE id = ?");
        $stmt->execute([$data['emplacement'], $camion['user_id']]);
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Emplacement synchronisé entre le camion et le franchisé'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>